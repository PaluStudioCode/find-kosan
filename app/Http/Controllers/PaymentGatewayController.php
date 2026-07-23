<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\OwnerWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    private function getDuitkuConfig()
    {
        $duitkuConfig = new \Duitku\Config(config('duitku.merchant_key'), config('duitku.merchant_code'));
        $duitkuConfig->setSandboxMode(config('duitku.env') === 'sandbox');
        return $duitkuConfig;
    }

    public function createInvoice(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id'
        ]);

        $invoice = Invoice::with(['tenant', 'tenancy.room.boardingHouse'])
            ->where('tenant_id', $request->user()->id)
            ->findOrFail($request->invoice_id);
        
        if ($invoice->status === 'lunas') {
            return response()->json(['error' => 'Tagihan ini sudah lunas'], 400);
        }

        $merchantOrderId = 'INV-' . $invoice->id . '-' . time();
        
        $params = array(
            'paymentAmount' => (int) $invoice->amount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Sewa Kamar ' . $invoice->tenancy->room->room_number . ' - ' . $invoice->tenancy->room->boardingHouse->name,
            'email' => $invoice->tenant->email,
            'phoneNumber' => $invoice->tenant->whatsapp_number ?? '081234567890',
            'customerDetail' => array(
                'firstName' => $invoice->tenant->name,
                'lastName' => '',
                'email' => $invoice->tenant->email,
                'phoneNumber' => $invoice->tenant->whatsapp_number ?? '081234567890',
            ),
            'itemDetails' => array(
                array(
                    'name' => 'Sewa Kamar ' . $invoice->tenancy->room->room_number,
                    'price' => (int) $invoice->amount,
                    'quantity' => 1
                )
            ),
            'callbackUrl' => url('/duitku/callback'),
            'returnUrl' => url('/tenant/tenancies/' . $invoice->tenancy_id),
            'expiryPeriod' => 1440
        );

        try {
            $response = \Duitku\Pop::createInvoice($params, $this->getDuitkuConfig());
            $jsonResponse = json_decode($response);
            
            if (isset($jsonResponse->reference)) {
                $invoice->update([
                    'payment_reference' => $jsonResponse->reference,
                    'payment_url' => $jsonResponse->paymentUrl ?? null
                ]);
                return response()->json($jsonResponse);
            }
            
            return response()->json(['error' => 'Gagal membuat tagihan Duitku', 'details' => $jsonResponse], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            $callback = \Duitku\Pop::callback($this->getDuitkuConfig());
            $notif = json_decode($callback);
            
            if (isset($notif->resultCode) && $notif->resultCode == "00") {
                $merchantOrderId = $notif->merchantOrderId ?? '';
                if (!preg_match('/^INV-(\d+)-\d+$/', $merchantOrderId, $matches)) {
                    return response()->json(['error' => 'Invalid merchant order ID'], 422);
                }
                
                $invoice = Invoice::find($matches[1]);
                if ($invoice && (float) $notif->amount === (float) $invoice->amount) {
                    $this->markInvoicePaid($invoice, $notif->paymentCode ?? 'Duitku');
                }
            }
            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Duitku Callback Error: ' . $e->getMessage());
            return response()->json(['error' => 'Bad Request'], 400);
        }
    }

    public function verifyLocal(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'merchant_order_id' => 'required|string',
        ]);
        
        $invoice = Invoice::where('payment_reference', $request->reference)
            ->where('tenant_id', $request->user()->id)
            ->firstOrFail();

        if (!preg_match('/^INV-' . $invoice->id . '-\d+$/', $request->merchant_order_id)) {
            return response()->json(['error' => 'Transaksi tidak cocok dengan tagihan'], 422);
        }

        $transaction = json_decode(
            \Duitku\Pop::transactionStatus($request->merchant_order_id, $this->getDuitkuConfig())
        );

        if (
            !$transaction
            || ($transaction->statusCode ?? null) !== '00'
            || ($transaction->reference ?? null) !== $invoice->payment_reference
            || (float) ($transaction->amount ?? 0) !== (float) $invoice->amount
        ) {
            return response()->json([
                'paid' => false,
                'message' => 'Pembayaran belum terverifikasi. Silakan tunggu beberapa saat lalu muat ulang halaman.',
            ], 409);
        }

        $this->markInvoicePaid($invoice, $transaction->paymentCode ?? 'Duitku');

        return response()->json(['paid' => true]);
    }

    private function markInvoicePaid(Invoice $invoice, string $paymentMethod): void
    {
        DB::transaction(function () use ($invoice, $paymentMethod) {
            $invoice = Invoice::with(['tenancy.room', 'tenant'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($invoice->status === 'lunas') {
                return;
            }

            $invoice->update([
                'status' => 'lunas',
                'payment_method' => $paymentMethod,
            ]);

            app(OwnerWalletService::class)->creditPaidInvoice($invoice);

            $tenancy = $invoice->tenancy;
            if ($tenancy->status === 'nonaktif') {
                $tenancy->update(['status' => 'aktif']);
            }

            $room = $tenancy->room;
            $activeTenants = $room->tenancies()->where('status', 'aktif')->sum('occupant_count');
            $room->update(['status' => $activeTenants >= $room->capacity ? 'terisi' : 'tersedia']);

            Payment::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'tenant_id' => $invoice->tenant_id,
                    'owner_id' => $invoice->owner_id,
                    'amount' => $invoice->amount,
                    'payment_date' => now(),
                    'status' => 'diterima',
                    'review_note' => 'Otomatis disetujui via Duitku',
                ]
            );

            $tenant = $invoice->tenant;
            if ($tenant && $tenant->whatsapp_number) {
                \App\Models\WhatsappNotification::updateOrCreate(
                    [
                        'invoice_id' => $invoice->id,
                        'message_type' => 'pembayaran_dikonfirmasi',
                        'scheduled_date' => today(),
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'phone_number' => $tenant->whatsapp_number,
                        'message_body' => "Halo {$tenant->name}, pembayaran sewa Anda otomatis DISETUJUI via Duitku. Selamat menempati kamar Anda!",
                        'status' => 'belum_dikirim',
                    ]
                );
            }
        });
    }
}
