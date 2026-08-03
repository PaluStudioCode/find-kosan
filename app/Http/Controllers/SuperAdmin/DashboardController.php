<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdminWallet;
use App\Models\BoardingHouse;
use App\Models\Invoice;
use App\Models\Report;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('SuperAdmin/Dashboard', [
            'metrics' => Inertia::defer(function () {
                return [
                    'totalUsers' => User::where('role', '!=', 'super_admin')->count(),
                    'totalOwners' => User::where('role', 'admin')->count(),
                    'totalTenants' => User::where('role', 'user')->count(),
                    'pendingKosVerifications' => BoardingHouse::where('status', 'menunggu_verifikasi')->count(),
                    'pendingReports' => Report::where('status', 'menunggu')->count(),
                    'pendingWithdrawals' => WithdrawalRequest::where('status', 'menunggu_persetujuan')->count(),
                ];
            }),
            'financials' => Inertia::defer(function () {
                return [
                    'totalGTV' => (float) Invoice::where('status', 'lunas')->sum('amount'),
                    'totalEscrow' => (float) AdminWallet::sum('available_balance') + (float) AdminWallet::sum('pending_withdrawal_balance'),
                    'totalPpn' => (float) Invoice::where('status', 'lunas')->sum('ppn_amount'),
                    'totalPph' => (float) WithdrawalRequest::where('status', 'selesai')->sum('pph_amount'),
                ];
            }),
            'charts' => Inertia::defer(function () {
                $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
                $usersGrowth = User::select('role', 'created_at')
                    ->where('created_at', '>=', $sixMonthsAgo)
                    ->get()
                    ->groupBy(function ($user) {
                        return $user->created_at->format('M Y');
                    });

                $growthLabels = [];
                for ($i = 5; $i >= 0; $i--) {
                    $growthLabels[] = now()->startOfMonth()->subMonths($i)->format('M Y');
                }

                $growthOwnersData = array_fill(0, 6, 0);
                $growthTenantsData = array_fill(0, 6, 0);

                foreach ($usersGrowth as $monthName => $users) {
                    $monthIndex = array_search($monthName, $growthLabels);
                    if ($monthIndex !== false) {
                        $growthOwnersData[$monthIndex] = $users->where('role', 'admin')->count();
                        $growthTenantsData[$monthIndex] = $users->where('role', 'user')->count();
                    }
                }

                $propertyStatuses = BoardingHouse::select('status', DB::raw('count(*) as total'))
                    ->groupBy('status')
                    ->get()
                    ->pluck('total', 'status')
                    ->toArray();

                $propertyStatusData = [
                    'Dipublikasikan' => $propertyStatuses['dipublikasikan'] ?? 0,
                    'Menunggu Verifikasi' => $propertyStatuses['menunggu_verifikasi'] ?? 0,
                    'Draft' => $propertyStatuses['draft'] ?? 0,
                    'Ditolak / Nonaktif' => ($propertyStatuses['ditolak'] ?? 0) + ($propertyStatuses['nonaktif'] ?? 0),
                ];

                return [
                    'growthLabels' => $growthLabels,
                    'growthOwnersData' => $growthOwnersData,
                    'growthTenantsData' => $growthTenantsData,
                    'propertyStatusLabels' => array_keys($propertyStatusData),
                    'propertyStatusData' => array_values($propertyStatusData),
                ];
            }),
            'recentVerifications' => Inertia::defer(function () {
                return BoardingHouse::with('admin')
                    ->where('status', 'menunggu_verifikasi')
                    ->latest()
                    ->take(5)
                    ->get();
            }),
            'recentReports' => Inertia::defer(function () {
                return Report::with(['reporter', 'boardingHouse'])
                    ->where('status', 'menunggu')
                    ->latest()
                    ->take(5)
                    ->get();
            }),
            'recentWithdrawals' => Inertia::defer(function () {
                return WithdrawalRequest::with('admin')
                    ->where('status', 'menunggu_persetujuan')
                    ->latest()
                    ->take(5)
                    ->get();
            }),
        ]);
    }
}
