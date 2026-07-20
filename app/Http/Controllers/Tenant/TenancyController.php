<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TenancyController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->id;

        $tenancies = auth()->user()->tenanciesAsTenant()->with(['room', 'boardingHouse', 'invoices' => function($q) {
            $q->latest();
        }])->latest()->paginate(10);
        
        $unpaidInvoicesCount = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'belum_dibayar')
            ->whereHas('tenancy', function($q) {
                $q->where('status', '!=', 'nonaktif');
            })
            ->count();
            
        $overdueInvoicesCount = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'belum_dibayar')
            ->where('due_date', '<', now()->startOfDay())
            ->whereHas('tenancy', function($q) {
                $q->where('status', '!=', 'nonaktif');
            })
            ->count();
        
        $pendingPaymentsCount = Payment::whereHas('invoice', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('status', 'menunggu_konfirmasi')->count();

        $recentInvoices = Invoice::with(['tenancy.room.boardingHouse'])
            ->where('tenant_id', $tenantId)
            ->whereHas('tenancy', function($q) {
                $q->where('status', '!=', 'nonaktif');
            })
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Payment::with(['invoice.tenancy.room.boardingHouse'])
            ->whereHas('invoice', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->latest()
            ->take(5)
            ->get();
        
        return Inertia::render('Tenant/Tenancies/Index', [
            'tenancies' => $tenancies,
            'metrics' => [
                'unpaidInvoices' => $unpaidInvoicesCount,
                'overdueInvoices' => $overdueInvoicesCount,
                'pendingPayments' => $pendingPaymentsCount,
            ],
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
        ]);
    }

    public function show(Tenancy $tenancy)
    {
        if ($tenancy->tenant_id !== auth()->id()) abort(403);
        $tenancy->load(['room', 'boardingHouse.owner', 'invoices.payments']);
        
        return Inertia::render('Tenant/Tenancies/Show', compact('tenancy'));
    }

    public function store(Request $request, Room $room)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'occupant_count' => 'required|integer|min:1|max:'.$room->capacity,
        ]);
        
        $activeTenants = $room->tenancies()->where('status', 'aktif')->sum('occupant_count');
        if ($activeTenants + $request->occupant_count > $room->capacity) {
            return back()->with('error', 'Kapasitas kamar tidak mencukupi.');
        }

        $tenancy = Tenancy::create([
            'tenant_id' => auth()->id(),
            'owner_id' => $room->boardingHouse->owner_id,
            'boarding_house_id' => $room->boarding_house_id,
            'room_id' => $room->id,
            'occupant_count' => $request->occupant_count,
            'start_date' => $request->start_date,
            'status' => 'nonaktif', // until paid and confirmed
        ]);

        $endDate = Carbon::parse($request->start_date);
        if ($room->price_period === 'harian') $endDate->addDay();
        elseif ($room->price_period === 'mingguan') $endDate->addWeek();
        elseif ($room->price_period === 'bulanan') $endDate->addMonth();
        elseif ($room->price_period === 'tahunan') $endDate->addYear();

        $invoice = Invoice::create([
            'tenancy_id' => $tenancy->id,
            'tenant_id' => auth()->id(),
            'owner_id' => $room->boardingHouse->owner_id,
            'period_start' => $request->start_date,
            'period_end' => $endDate,
            'amount' => $room->price,
            'due_date' => Carbon::parse($request->start_date)->addDays(1),
            'status' => 'belum_dibayar',
        ]);

        // Create WhatsApp Notification to Owner
        $owner = $room->boardingHouse->owner;
        if ($owner && $owner->whatsapp_number) {
            \App\Models\WhatsappNotification::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => $owner->id, // Store owner ID here as recipient
                'phone_number' => $owner->whatsapp_number,
                'message_type' => 'pembayaran_baru',
                'message_body' => "Halo {$owner->name}, ada pengajuan sewa baru untuk kamar {$room->name} di {$room->boardingHouse->name}. Silakan cek dashboard Anda.",
                'scheduled_date' => today(),
                'status' => 'belum_dikirim',
            ]);
        }

        return redirect()->route('tenant.tenancies.show', $tenancy->id)->with('success', 'Pengajuan sewa berhasil dibuat, silakan lakukan pembayaran.');
    }

    public function uploadPayment(Request $request, Invoice $invoice)
    {
        if ($invoice->tenant_id !== auth()->id()) abort(403);
        
        $request->validate([
            'proof_file' => 'required|image|max:2048'
        ]);

        $path = $request->file('proof_file')->store('payments', 'local');

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => auth()->id(),
            'owner_id' => $invoice->owner_id,
            'amount' => $invoice->amount,
            'payment_date' => now(),
            'proof_file_path' => '/storage/' . $path,
            'status' => 'menunggu_konfirmasi',
        ]);

        $invoice->update(['status' => 'menunggu_konfirmasi']);

        // Notification to Owner
        $owner = $invoice->owner;
        if ($owner && $owner->whatsapp_number) {
            \App\Models\WhatsappNotification::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => $owner->id,
                'phone_number' => $owner->whatsapp_number,
                'message_type' => 'pembayaran_baru',
                'message_body' => "Halo {$owner->name}, ada pembayaran baru sebesar Rp" . number_format($invoice->amount, 0, ',', '.') . " yang menunggu konfirmasi. Silakan cek dashboard Anda.",
                'scheduled_date' => today(),
                'status' => 'belum_dikirim',
            ]);
        }

        return back()->with('success', 'Bukti pembayaran berhasil diunggah.');
    }
}
