<?php

namespace App\Http\Controllers\User;

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
        $userId = auth()->user()->id;

        $tenancies = auth()->user()->tenanciesAsTenant()->with(['room', 'boardingHouse', 'invoices' => function($q) {
            $q->latest();
        }])->latest()->paginate(10);
        
        $unpaidInvoicesCount = Invoice::where('user_id', $userId)
            ->where('status', 'belum_dibayar')
            ->whereHas('tenancy', function($q) {
                $q->where('status', '!=', 'nonaktif');
            })
            ->count();
            
        $overdueInvoicesCount = Invoice::where('user_id', $userId)
            ->where('status', 'belum_dibayar')
            ->where('due_date', '<', now()->startOfDay())
            ->whereHas('tenancy', function($q) {
                $q->where('status', '!=', 'nonaktif');
            })
            ->count();
        
        $pendingPaymentsCount = Payment::whereHas('invoice', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('status', 'menunggu_konfirmasi')->count();

        $recentInvoices = Invoice::with(['tenancy.room.boardingHouse'])
            ->where('user_id', $userId)
            ->whereHas('tenancy', function($q) {
                $q->where('status', '!=', 'nonaktif');
            })
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Payment::with(['invoice.tenancy.room.boardingHouse'])
            ->whereHas('invoice', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->latest()
            ->take(5)
            ->get();
        
        return Inertia::render('User/Tenancies/Index', [
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
        if ($tenancy->user_id !== auth()->id()) abort(403);
        $tenancy->load(['room', 'boardingHouse.admin', 'invoices.payments']);
        
        return Inertia::render('User/Tenancies/Show', compact('tenancy'));
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
            'user_id' => auth()->id(),
            'admin_id' => $room->boardingHouse->admin_id,
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
            'user_id' => auth()->id(),
            'admin_id' => $room->boardingHouse->admin_id,
            'period_start' => $request->start_date,
            'period_end' => $endDate,
            'amount' => $room->price,
            'due_date' => Carbon::parse($request->start_date)->addDays(1),
            'status' => 'belum_dibayar',
        ]);

        if ($room->boardingHouse->admin->whatsapp_number) {
            \App\Models\WhatsappNotification::create([
                'invoice_id' => $invoice->id,
                'user_id' => $room->boardingHouse->admin_id,
                'admin_id' => $room->boardingHouse->admin_id,
                'send_via' => 'admin',
                'phone_number' => $room->boardingHouse->admin->whatsapp_number,
                'message_type' => 'pembayaran_baru',
                'message_body' => "Penyewaan baru! Mohon periksa tagihan Anda.",
                'scheduled_date' => now(),
                'status' => 'belum_dikirim',
            ]);
        }

        return redirect()->route('user.tenancies.show', $tenancy->id);
    }

    public function uploadPayment(\Illuminate\Http\Request $request, \App\Models\Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) abort(403);
        
        $request->validate([
            'proof_file' => 'required|image|max:2048'
        ]);

        $path = $request->file('proof_file')->store('payments', 'public');

        $payment = \App\Models\Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'admin_id' => $invoice->admin_id,
            'amount' => $invoice->amount,
            'payment_date' => now(),
            'proof_file_path' => '/storage/' . $path,
            'status' => 'menunggu_konfirmasi',
        ]);

        $invoice->update(['status' => 'menunggu_konfirmasi']);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah.');
    }
}