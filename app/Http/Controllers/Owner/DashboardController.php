<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Room;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = auth()->user()->id;

        $totalKos = BoardingHouse::where('owner_id', $ownerId)->count();
        $totalRooms = Room::whereHas('boardingHouse', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->count();
        
        $availableRooms = Room::whereHas('boardingHouse', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->where('status', 'tersedia')->count();

        $occupiedRooms = Room::whereHas('boardingHouse', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->where('status', 'disewa')->count();

        $pendingPaymentsCount = Payment::whereHas('invoice', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })->where('status', 'menunggu_konfirmasi')->count();

        $recentPayments = Payment::with(['invoice.tenancy.room.boardingHouse', 'invoice.tenant'])
            ->whereHas('invoice', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->latest()
            ->take(5)
            ->get();

        $activityLogs = \App\Models\ActivityLog::where('user_id', $ownerId)
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Owner/Dashboard', [
            'metrics' => [
                'totalKos' => $totalKos,
                'totalRooms' => $totalRooms,
                'availableRooms' => $availableRooms,
                'occupiedRooms' => $occupiedRooms,
                'pendingPayments' => $pendingPaymentsCount,
            ],
            'recentPayments' => $recentPayments,
            'activityLogs' => $activityLogs,
        ]);
    }
}
