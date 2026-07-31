<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BoardingHouse;
use App\Models\Report;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Kartu Metrik Utama
        $totalUsers = User::count();
        $totalOwners = User::where('role', 'admin')->count();
        $totalTenants = User::where('role', 'user')->count();
        
        $pendingKosVerifications = BoardingHouse::where('status', 'menunggu_verifikasi')->count();
        $pendingReports = Report::where('status', 'menunggu_diproses')->count();
        $pendingWithdrawals = WithdrawalRequest::where('status', 'menunggu_persetujuan')->count();

        // 2. Data Visualisasi (Grafik)
        
        // Pertumbuhan Pengguna (6 Bulan Terakhir)
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $usersGrowth = User::select('role', 'created_at')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->get()
            ->groupBy(function($user) {
                return $user->created_at->format('M Y');
            });

        // Menyusun array bulan (label)
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

        // Sebaran Status Properti
        $propertyStatuses = BoardingHouse::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Status: draft, menunggu_verifikasi, dipublikasikan, nonaktif, ditolak
        $propertyStatusData = [
            'Dipublikasikan' => $propertyStatuses['dipublikasikan'] ?? 0,
            'Menunggu Verifikasi' => $propertyStatuses['menunggu_verifikasi'] ?? 0,
            'Draft' => $propertyStatuses['draft'] ?? 0,
            'Ditolak / Nonaktif' => ($propertyStatuses['ditolak'] ?? 0) + ($propertyStatuses['nonaktif'] ?? 0)
        ];


        // 3. Antrean (Tabel)
        $recentVerifications = BoardingHouse::with('admin')
            ->where('status', 'menunggu_verifikasi')
            ->latest()
            ->take(5)
            ->get();

        $recentReports = Report::with(['reporter', 'reportable'])
            ->where('status', 'menunggu_diproses')
            ->latest()
            ->take(5)
            ->get();
            
        $recentWithdrawals = WithdrawalRequest::with('admin')
            ->where('status', 'menunggu_persetujuan')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('SuperAdmin/Dashboard', [
            'metrics' => [
                'totalUsers' => $totalUsers,
                'totalOwners' => $totalOwners,
                'totalTenants' => $totalTenants,
                'pendingKosVerifications' => $pendingKosVerifications,
                'pendingReports' => $pendingReports,
                'pendingWithdrawals' => $pendingWithdrawals,
            ],
            'charts' => [
                'growthLabels' => $growthLabels,
                'growthOwnersData' => $growthOwnersData,
                'growthTenantsData' => $growthTenantsData,
                'propertyStatusLabels' => array_keys($propertyStatusData),
                'propertyStatusData' => array_values($propertyStatusData),
            ],
            'recentVerifications' => $recentVerifications,
            'recentReports' => $recentReports,
            'recentWithdrawals' => $recentWithdrawals,
        ]);
    }
}
