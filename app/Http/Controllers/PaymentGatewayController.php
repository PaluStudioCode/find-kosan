<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WhatsappNotification;
use App\Services\AdminWalletService;
use Duitku\Config;
use Duitku\Pop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    private function getDuitkuConfig()
    {
        $duitkuConfig = new Config(config('duitku.merchant_key'), config('duitku.merchant_code'));
        $duitkuConfig->setSandboxMode(config('duitku.env') === 'sandbox');

        return $duitkuConfig;
    }

    public function createInvoice(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $invoice = Invoice::with(['user', 'tenancy.room.boardingHouse'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($request->invoice_id);

        if ($invoice->status === 'lunas') {
            return response()->json(['error' => 'Tagihan ini sudah lunas'], 400);
        }

        $merchantOrderId = 'INV-'.$invoice->id.'-'.time();

        $params = [
            'paymentAmount' => (int) $invoice->amount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Sewa Kamar '.$invoice->tenancy->room->room_number.' - '.$invoice->tenancy->room->boardingHouse->name,
            'email' => $invoice->user->email,
            'phoneNumber' => $invoice->user->whatsapp_number ?? '081234567890',
            'customerDetail' => [
                'firstName' => $invoice->user->name,
                'lastName' => '',
                'email' => $invoice->user->email,
                'phoneNumber' => $invoice->user->whatsapp_number ?? '081234567890',
            ],
            'itemDetails' => [
                [
                    'name' => 'Sewa Kamar '.$invoice->tenancy->room->room_number,
                    'price' => (int) $invoice->amount,
                    'quantity' => 1,
                ],
            ],
            'callbackUrl' => url('/duitku/callback'),
            'returnUrl' => url('/user/tenancies/'.$invoice->tenancy_id),
            'expiryPeriod' => 1440,
        ];

        try {
            $response = Pop::createInvoice($params, $this->getDuitkuConfig());
            $jsonResponse = json_decode($response);

            if (isset($jsonResponse->reference)) {
                $invoice->update([
                    'payment_reference' => $jsonResponse->reference,
                    'payment_url' => $jsonResponse->paymentUrl ?? null,
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
            $callback = Pop::callback($this->getDuitkuConfig());
            $notif = json_decode($callback);

            if (isset($notif->resultCode) && $notif->resultCode == '00') {
                $merchantOrderId = $notif->merchantOrderId ?? '';
                if (! preg_match('/^INV-(\d+)-\d+$/', $merchantOrderId, $matches)) {
                    return response()->json(['error' => 'Invalid merchant order ID'], 422);
                }

                $invoice = Invoice::find($matches[1]);
                if ($invoice && (float) $notif->amount === (float) $invoice->amount) {
                    $this->markInvoicePaid($invoice, $notif->paymentCode ?? 'Duitku');
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Duitku Callback Error: '.$e->getMessage());

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
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! preg_match('/^INV-'.$invoice->id.'-\d+$/', $request->merchant_order_id)) {
            return response()->json(['error' => 'Transaksi tidak cocok dengan tagihan'], 422);
        }

        $transaction = json_decode(
            Pop::transactionStatus($request->merchant_order_id, $this->getDuitkuConfig())
        );

        if (
            ! $transaction
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
            $invoice = Invoice::with(['tenancy.room', 'user'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($invoice->status === 'lunas') {
                return;
            }

            $invoice->update([
                'status' => 'lunas',
                'payment_method' => $paymentMethod,
            ]);

            app(AdminWalletService::class)->creditPaidInvoice($invoice);

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
                    'user_id' => $invoice->user_id,
                    'admin_id' => $invoice->admin_id,
                    'amount' => $invoice->amount,
                    'payment_date' => now(),
                    'status' => 'diterima',
                    'review_note' => 'Otomatis disetujui via Duitku',
                ]
            );

            $user = $invoice->user;
            $admin = User::find($invoice->admin_id);
            $userName = $user->name ?? 'Penyewa';
            $kosName = $invoice->tenancy->room->boardingHouse->name ?? 'kos';
            $roomNumber = $invoice->tenancy->room->room_number ?? '';

            if ($admin && $admin->whatsapp_number) {
                WhatsappNotification::updateOrCreate(
                    [
                        'invoice_id' => $invoice->id,
                        'message_type' => 'pembayaran_dikonfirmasi',
                        'scheduled_date' => today(),
                    ],
                    [
                        'user_id' => $invoice->user_id,
                        'admin_id' => $invoice->admin_id,
                        'send_via' => 'admin',
                        'phone_number' => $admin->whatsapp_number,
                        'message_body' => "Halo {$admin->name}, pembayaran sewa dari {$userName} untuk kamar {$roomNumber} di {$kosName} sebesar Rp".number_format($invoice->amount, 0, ',', '.')." telah diterima melalui Duitku.\n\nLihat detail sewa: ".route('admin.tenancies.show', $invoice->tenancy_id),
                        'status' => 'belum_dikirim',
                    ]
                );
            }

            if ($user && $user->whatsapp_number) {
                WhatsappNotification::updateOrCreate(
                    [
                        'invoice_id' => $invoice->id,
                        'message_type' => 'pembayaran_berhasil_penyewa',
                        'scheduled_date' => today(),
                    ],
                    [
                        'user_id' => $invoice->user_id,
                        'admin_id' => $invoice->admin_id,
                        'send_via' => 'admin',
                        'phone_number' => $user->whatsapp_number,
                        'message_body' => "Halo {$userName}, pembayaran sewa kamar {$roomNumber} di {$kosName} sebesar Rp".number_format($invoice->amount, 0, ',', '.')." telah BERHASIL dikonfirmasi. Terima kasih!\n\nLihat detail sewa Anda:\n".route('user.tenancies.show', $invoice->tenancy_id),
                        'status' => 'belum_dikirim',
                    ]
                );
            }
        });
    }
}
