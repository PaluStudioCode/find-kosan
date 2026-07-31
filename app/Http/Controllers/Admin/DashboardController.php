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

        // 1. Pendapatan Bulan Ini
        $currentMonthRevenue = Invoice::where('admin_id', $adminId)
            ->where('status', 'lunas')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('amount');

        // 2. Tingkat Keterisian (Occupancy Rate)
        $totalRooms = Room::whereHas('boardingHouse', function ($q) use ($adminId) {
            $q->where('admin_id', $adminId);
        })->count();
        $occupiedRooms = Room::whereHas('boardingHouse', function ($q) use ($adminId) {
            $q->where('admin_id', $adminId);
        })->where('status', 'terisi')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        // 3. Tagihan Belum Dibayar / Jatuh Tempo
        $pendingInvoicesCount = Invoice::where('admin_id', $adminId)
            ->whereIn('status', ['belum_dibayar', 'jatuh_tempo'])
            ->count();

        // 4. Saldo Dompet
        $wallet = AdminWallet::where('admin_id', $adminId)->first();
        $walletBalance = $wallet ? $wallet->available_balance : 0;

        // Fetch Recent Transactions (lunas invoices)
        $recentTransactions = Invoice::with(['tenancy.room.boardingHouse', 'user'])
            ->where('admin_id', $adminId)
            ->where('status', 'lunas')
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Revenue Chart Data (Last 6 Months)
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

        // Property Capacity Status
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

        // Upcoming Due Invoices (H-3 to Overdue)
        $upcomingDueInvoices = Invoice::with(['tenancy.room.boardingHouse', 'user'])
            ->where('admin_id', $adminId)
            ->whereIn('status', ['belum_dibayar', 'jatuh_tempo'])
            ->where('due_date', '<=', now()->addDays(3))
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        // Vacant Rooms List
        $vacantRooms = Room::with(['boardingHouse'])
            ->whereHas('boardingHouse', function ($q) use ($adminId) {
                $q->where('admin_id', $adminId);
            })
            ->where('status', 'tersedia')
            ->latest()
            ->take(5)
            ->get();

        // Recent Reviews
        $recentReviews = BoardingHouseReview::with(['user', 'boardingHouse'])
            ->whereHas('boardingHouse', function ($q) use ($adminId) {
                $q->where('admin_id', $adminId);
            })
            ->latest()
            ->take(5)
            ->get();

        $activityLogs = ActivityLog::where('user_id', $adminId)
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'currentMonthRevenue' => $currentMonthRevenue,
                'occupancyRate' => $occupancyRate,
                'occupiedRooms' => $occupiedRooms,
                'totalRooms' => $totalRooms,
                'pendingInvoices' => $pendingInvoicesCount,
                'walletBalance' => $walletBalance,
            ],
            'recentTransactions' => $recentTransactions,
            'activityLogs' => $activityLogs,
            'upcomingDueInvoices' => $upcomingDueInvoices,
            'vacantRooms' => $vacantRooms,
            'recentReviews' => $recentReviews,
            'charts' => [
                'revenueLabels' => $revenueChartLabels,
                'revenueData' => $revenueChartData,
                'propertiesCapacity' => $propertiesCapacity,
            ],
        ]);
    }
}
