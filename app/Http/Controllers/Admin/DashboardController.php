<?php

namespace App\Http\Controllers\Admin;

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
        $totalOwners = User::where('role', 'pemilik_kos')->count();
        $totalTenants = User::where('role', 'penyewa')->count();
        
        $pendingKosVerifications = BoardingHouse::where('status', 'menunggu_verifikasi')->count();
        $pendingReports = Report::where('status', 'menunggu_diproses')->count();
        $pendingWithdrawals = WithdrawalRequest::where('status', 'menunggu_persetujuan')->count();

        // 2. Data Visualisasi (Grafik)
        
        // Pertumbuhan Pengguna (6 Bulan Terakhir)
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $usersGrowth = User::select(
                DB::raw("DATE_FORMAT(created_at, '%b %Y') as month_name"),
                DB::raw("MONTH(created_at) as month"),
                DB::raw("YEAR(created_at) as year"),
                DB::raw("role"),
                DB::raw("count(*) as total")
            )
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('year', 'month', 'month_name', 'role')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Menyusun array bulan (label)
        $growthLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $growthLabels[] = now()->subMonths($i)->format('M Y');
        }

        $growthOwnersData = array_fill(0, 6, 0);
        $growthTenantsData = array_fill(0, 6, 0);

        foreach ($usersGrowth as $record) {
            $monthIndex = array_search($record->month_name, $growthLabels);
            if ($monthIndex !== false) {
                if ($record->role === 'pemilik_kos') {
                    $growthOwnersData[$monthIndex] = $record->total;
                } else if ($record->role === 'penyewa') {
                    $growthTenantsData[$monthIndex] = $record->total;
                }
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
        $recentVerifications = BoardingHouse::with('owner')
            ->where('status', 'menunggu_verifikasi')
            ->latest()
            ->take(5)
            ->get();

        $recentReports = Report::with(['reporter', 'reportable'])
            ->where('status', 'menunggu_diproses')
            ->latest()
            ->take(5)
            ->get();
            
        $recentWithdrawals = WithdrawalRequest::with('owner')
            ->where('status', 'menunggu_persetujuan')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
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
