<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenancy;

class RoomController extends Controller
{
    public function store(Request $request, BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'room_number' => 'required|string|max:50',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_period' => 'required|in:harian,mingguan,bulanan,tahunan',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:tersedia,penuh,disewa,dalam_perbaikan',
            'facilities' => 'array',
            'facilities.*' => 'exists:facilities,id'
        ]);

        DB::transaction(function () use ($validated, $request, $kos) {
            $room = $kos->rooms()->create($validated);
            if ($request->has('facilities')) {
                $room->facilities()->sync($request->facilities);
            }
        });

        return back()->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function update(Request $request, BoardingHouse $kos, Room $room)
    {
        if ($kos->owner_id !== auth()->id() || $room->boarding_house_id !== $kos->id) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'room_number' => 'required|string|max:50',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_period' => 'required|in:harian,mingguan,bulanan,tahunan',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:tersedia,penuh,disewa,dalam_perbaikan',
            'facilities' => 'array',
            'facilities.*' => 'exists:facilities,id'
        ]);

        $activeTenants = Tenancy::where('room_id', $room->id)->where('status', 'aktif')->count();
        if ($validated['capacity'] < $activeTenants) {
            return back()->withErrors(['capacity' => 'Kapasitas tidak boleh lebih kecil dari penyewa aktif (' . $activeTenants . ').'])->withInput();
        }

        DB::transaction(function () use ($validated, $request, $room) {
            $room->update($validated);
            if ($request->has('facilities')) {
                $room->facilities()->sync($request->facilities);
            }
        });

        return back()->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function destroy(BoardingHouse $kos, Room $room)
    {
        if ($kos->owner_id !== auth()->id() || $room->boarding_house_id !== $kos->id) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }
        
        $activeTenants = Tenancy::where('room_id', $room->id)->where('status', 'aktif')->count();
        if ($activeTenants > 0) {
            return back()->with('error', 'Kamar tidak bisa dihapus karena masih ada penyewa aktif.');
        }

        $room->delete();
        return back()->with('success', 'Kamar berhasil dihapus.');
    }
}
