<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->id;

        $unpaidInvoicesCount = Invoice::where('tenant_id', $tenantId)->where('status', 'belum_dibayar')->count();
        $overdueInvoicesCount = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'belum_dibayar')
            ->where('due_date', '<', now()->startOfDay())
            ->count();
        
        $pendingPaymentsCount = Payment::whereHas('invoice', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('status', 'menunggu_konfirmasi')->count();

        $recentInvoices = Invoice::with(['tenancy.room.boardingHouse'])
            ->where('tenant_id', $tenantId)
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

        return Inertia::render('Tenant/Dashboard', [
            'metrics' => [
                'unpaidInvoices' => $unpaidInvoicesCount,
                'overdueInvoices' => $overdueInvoicesCount,
                'pendingPayments' => $pendingPaymentsCount,
            ],
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
        ]);
    }
}
