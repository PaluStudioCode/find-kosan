<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Tenancy;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenancyController extends Controller
{
    public function index()
    {
        $tenancies = auth()->user()->tenanciesAsOwner()->with(['room', 'tenant', 'invoices' => function($q) {
            $q->latest();
        }])->latest()->paginate(10);
        
        return Inertia::render('Owner/Tenancies/Index', compact('tenancies'));
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
                \App\Models\WhatsappNotification::create([
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $tenant->id,
                    'phone_number' => $tenant->whatsapp_number,
                    'message_type' => 'pembayaran_dikonfirmasi',
                    'message_body' => "Halo {$tenant->name}, pembayaran sewa Anda sebesar Rp" . number_format($invoice->amount, 0, ',', '.') . " telah DISETUJUI. Selamat menempati kamar Anda!",
                    'scheduled_date' => today(),
                    'status' => 'belum_dikirim',
                ]);
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
                \App\Models\WhatsappNotification::create([
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $tenant->id,
                    'phone_number' => $tenant->whatsapp_number,
                    'message_type' => 'pembayaran_dikonfirmasi',
                    'message_body' => "Halo {$tenant->name}, pembayaran sewa Anda sebesar Rp" . number_format($invoice->amount, 0, ',', '.') . " DITOLAK. Catatan: " . ($request->review_note ?? 'Tidak ada') . ". Silakan unggah bukti yang benar.",
                    'scheduled_date' => today(),
                    'status' => 'belum_dikirim',
                ]);
            }

            return back()->with('success', 'Pembayaran ditolak.');
        }
    }
}
