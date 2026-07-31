<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenancy;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AdminWalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenancyController extends Controller
{
    public function index(Request $request)
    {
        $admin = auth()->user();
        $kosId = $request->get('kos_id');

        $query = $admin->tenanciesAsOwner()
            ->where(function($q) {
                $q->where('status', '!=', 'nonaktif')
                  ->orWhereHas('invoices.payments');
            });

        if ($kosId && $kosId !== 'all') {
            $query->where('boarding_house_id', $kosId);
        }

        $tenancies = $query->with(['room.boardingHouse', 'user', 'invoices' => function($q) {
                $q->latest();
            }])->latest()->paginate(10)->withQueryString();
        
        $properties = \App\Models\BoardingHouse::where('admin_id', $admin->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Admin/Tenancies/Index', [
            'tenancies' => $tenancies,
            'properties' => $properties,
            'filters' => ['kos_id' => $kosId]
        ]);
    }

    public function show(Tenancy $tenancy)
    {
        if ($tenancy->admin_id !== auth()->id()) abort(403);
        $tenancy->load(['room.boardingHouse', 'user', 'invoices.payments']);
        
        return Inertia::render('Admin/Tenancies/Show', compact('tenancy'));
    }

    public function endTenancy(Tenancy $tenancy)
    {
        if ($tenancy->admin_id !== auth()->id()) abort(403);
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

    public function confirmPayment(\Illuminate\Http\Request $request, \App\Models\Payment $payment)
    {
        if ($payment->admin_id !== auth()->id()) abort(403);
        
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
            app(\App\Services\AdminWalletService::class)->creditPaidInvoice($invoice);
            
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
            
            return back()->with('success', 'Pembayaran ditolak.');
        }
    }
}