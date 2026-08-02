<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminWallet;
use App\Models\BoardingHouse;
use App\Models\BoardingHouseReview;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $adminId = auth()->user()->id;

        return Inertia::render('Admin/Dashboard', [
            'metrics' => Inertia::defer(function () use ($adminId) {
                $currentMonthRevenue = Invoice::where('admin_id', $adminId)
                    ->where('status', 'lunas')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->sum('amount');

                $totalRooms = Room::whereHas('boardingHouse', function ($q) use ($adminId) {
                    $q->where('admin_id', $adminId);
                })->count();
                $occupiedRooms = Room::whereHas('boardingHouse', function ($q) use ($adminId) {
                    $q->where('admin_id', $adminId);
                })->where('status', 'terisi')->count();
                $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

                $pendingInvoicesCount = Invoice::where('admin_id', $adminId)
                    ->whereIn('status', ['belum_dibayar', 'jatuh_tempo'])
                    ->count();

                $wallet = AdminWallet::where('admin_id', $adminId)->first();
                $walletBalance = $wallet ? $wallet->available_balance : 0;

                return [
                    'currentMonthRevenue' => $currentMonthRevenue,
                    'occupancyRate' => $occupancyRate,
                    'occupiedRooms' => $occupiedRooms,
                    'totalRooms' => $totalRooms,
                    'pendingInvoices' => $pendingInvoicesCount,
                    'walletBalance' => $walletBalance,
                ];
            }),

            'recentTransactions' => Inertia::defer(function () use ($adminId) {
                return Invoice::with(['tenancy.room.boardingHouse', 'user'])
                    ->where('admin_id', $adminId)
                    ->where('status', 'lunas')
                    ->latest('updated_at')
                    ->take(5)
                    ->get();
            }),

            'activityLogs' => Inertia::defer(function () use ($adminId) {
                return ActivityLog::where('user_id', $adminId)
                    ->latest()
                    ->take(5)
                    ->get();
            }),

            'upcomingDueInvoices' => Inertia::defer(function () use ($adminId) {
                return Invoice::with(['tenancy.room.boardingHouse', 'user'])
                    ->where('admin_id', $adminId)
                    ->whereIn('status', ['belum_dibayar', 'jatuh_tempo'])
                    ->where('due_date', '<=', now()->addDays(3))
                    ->orderBy('due_date', 'asc')
                    ->take(5)
                    ->get();
            }),

            'vacantRooms' => Inertia::defer(function () use ($adminId) {
                return Room::with(['boardingHouse'])
                    ->whereHas('boardingHouse', function ($q) use ($adminId) {
                        $q->where('admin_id', $adminId);
                    })
                    ->where('status', 'tersedia')
                    ->latest()
                    ->take(5)
                    ->get();
            }),

            'recentReviews' => Inertia::defer(function () use ($adminId) {
                return BoardingHouseReview::with(['user', 'boardingHouse'])
                    ->whereHas('boardingHouse', function ($q) use ($adminId) {
                        $q->where('admin_id', $adminId);
                    })
                    ->latest()
                    ->take(5)
                    ->get();
            }),

            'charts' => Inertia::defer(function () use ($adminId) {
                $revenueChartLabels = [];
                $revenueChartData = [];
                for ($i = 5; $i >= 0; $i--) {
                    $date = now()->startOfMonth()->subMonths($i);
                    $revenueChartLabels[] = $date->translatedFormat('M Y');
                    $monthlyRevenue = Invoice::where('admin_id', $adminId)
                        ->where('status', 'lunas')
                        ->whereMonth('updated_at', $date->month)
                        ->whereYear('updated_at', $date->year)
                        ->sum('amount');
                    $revenueChartData[] = $monthlyRevenue;
                }

                $propertiesCapacity = BoardingHouse::where('admin_id', $adminId)
                    ->withCount([
                        'rooms as total_rooms',
                        'rooms as occupied_rooms' => function ($q) {
                            $q->where('status', 'terisi');
                        },
                    ])
                    ->get()
                    ->map(function ($kos) {
                        return [
                            'name' => $kos->name,
                            'occupied_rooms' => $kos->occupied_rooms,
                            'vacant_rooms' => $kos->total_rooms - $kos->occupied_rooms,
                        ];
                    });

                return [
                    'revenueLabels' => $revenueChartLabels,
                    'revenueData' => $revenueChartData,
                    'propertiesCapacity' => $propertiesCapacity,
                ];
            }),
        ]);
    }
}
