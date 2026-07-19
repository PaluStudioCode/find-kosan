<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BoardingHouse;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalUsers = User::count();
        $totalOwners = User::where('role', 'pemilik_kos')->count();
        $totalTenants = User::where('role', 'penyewa')->count();
        
        $pendingKosVerifications = BoardingHouse::where('status', 'menunggu_verifikasi')->count();
        $pendingReports = Report::where('status', 'menunggu_diproses')->count();

        $recentVerifications = BoardingHouse::with('owner')
            ->where('status', 'menunggu_verifikasi')
            ->latest()
            ->take(5)
            ->get();

        $recentReports = Report::with(['reporter'])
            ->where('status', 'menunggu_diproses')
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
            ],
            'recentVerifications' => $recentVerifications,
            'recentReports' => $recentReports,
        ]);
    }
}
