<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Tenancy;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\OwnerWalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenancyController extends Controller
{
    public function index(Request $request)
    {
        $owner = auth()->user();
        $kosId = $request->get('kos_id');

        $query = $owner->tenanciesAsOwner()
            ->where(function($q) {
                $q->where('status', '!=', 'nonaktif')
                  ->orWhereHas('invoices.payments');
            });

        if ($kosId && $kosId !== 'all') {
            $query->where('boarding_house_id', $kosId);
        }

        $tenancies = $query->with(['room.boardingHouse', 'tenant', 'invoices' => function($q) {
                $q->latest();
            }])->latest()->paginate(10)->withQueryString();
        
        $properties = \App\Models\BoardingHouse::where('owner_id', $owner->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Owner/Tenancies/Index', [
            'tenancies' => $tenancies,
            'properties' => $properties,
            'filters' => ['kos_id' => $kosId]
        ]);
    }

    public function show(Tenancy $tenancy)
    {
        if ($tenancy->owner_id !== auth()->id()) abort(403);
        $tenancy->load(['room.boardingHouse', 'tenant', 'invoices.payments']);
        
        return Inertia::render('Owner/Tenancies/Show', compact('tenancy'));
    }

    public function confirmPayment(Request $request, Payment $payment)
    {
        if ($payment->owner_id !== auth()->id()) abort(403);
        
        $request->validate([
            'action' => 'required|in:approve,reject',
            'review_note' => 'nullable|string'
        ]);

        if ($payment->status !== 'menunggu_konfirmasi') {
            return back()->with('error', 'Pembayaran ini sudah selesai diproses sebelumnya.');
        }

        $invoice = $payment->invoice;
        $tenancy = $invoice->tenancy;

        if ($request->action === 'approve') {
            $payment->update([
                'status' => 'diterima',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_note' => $request->review_note,
            ]);
            $invoice->update(['status' => 'lunas']);
            app(OwnerWalletService::class)->creditPaidInvoice($invoice);
            
            if ($tenancy->status === 'nonaktif') {
                $tenancy->update(['status' => 'aktif']);
            }
            
            $room = $tenancy->room;
            $activeTenants = $room->tenancies()->where('status', 'aktif')->sum('occupant_count');
            if ($activeTenants >= $room->capacity) {
                $room->update(['status' => 'terisi']);
            } else {
                $room->update(['status' => 'tersedia']);
            }

            // Notification to Tenant
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
                        'message_body' => "Halo {$tenant->name}, pembayaran sewa Anda sebesar Rp" . number_format($invoice->amount, 0, ',', '.') . " telah DISETUJUI. Selamat menempati kamar Anda!",
                        'status' => 'belum_dikirim',
                    ]
                );
            }

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'payment.approved',
                'description' => "Menyetujui pembayaran untuk tagihan #{$invoice->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return back()->with('success', 'Pembayaran disetujui.');
        } else {
            $payment->update([
                'status' => 'ditolak',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_note' => $request->review_note,
            ]);
            $invoice->update(['status' => 'belum_dibayar']);
            
            // Notification to Tenant
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
                        'message_body' => "Halo {$tenant->name}, pembayaran sewa Anda sebesar Rp" . number_format($invoice->amount, 0, ',', '.') . " DITOLAK. Catatan: " . ($request->review_note ?? 'Tidak ada') . ". Silakan unggah bukti yang benar.",
                        'status' => 'belum_dikirim',
                    ]
                );
            }

            return back()->with('success', 'Pembayaran ditolak.');
        }
    }

    public function endTenancy(Tenancy $tenancy)
    {
        if ($tenancy->owner_id !== auth()->id()) abort(403);
        if ($tenancy->status !== 'aktif') {
            return back()->with('error', 'Hanya penyewaan aktif yang dapat diakhiri.');
        }

        $tenancy->update(['status' => 'selesai']);

        $room = $tenancy->room;
        $activeTenants = $room->tenancies()->where('status', 'aktif')->sum('occupant_count');
        if ($activeTenants >= $room->capacity) {
            $room->update(['status' => 'terisi']);
        } else {
            $room->update(['status' => 'tersedia']);
        }

        return back()->with('success', 'Masa sewa telah diakhiri. Status kamar otomatis diperbarui menjadi tersedia jika kapasitas mencukupi.');
    }
}
