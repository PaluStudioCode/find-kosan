<?php

namespace App\Http\Controllers\Admin;

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
        if ($kos->admin_id !== auth()->id()) abort(403);
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

        if ($kos->rooms()->where('room_number', $validated['room_number'])->exists()) {
            return back()->withErrors(['room_number' => 'Nomor kamar ini sudah digunakan.'])->withInput();
        }

        DB::transaction(function () use ($validated, $request, $kos) {
            $room = $kos->rooms()->create($validated);
            if ($request->has('facilities')) {
                $room->facilities()->sync($request->facilities);
            }
        });

        return back()->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function bulkStore(Request $request, BoardingHouse $kos)
    {
        if ($kos->admin_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'bulk_prefix' => 'required|string|max:50',
            'bulk_start' => 'required|integer|min:1',
            'bulk_count' => 'required|integer|min:1|max:50',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_period' => 'required|in:harian,mingguan,bulanan,tahunan',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:tersedia,penuh,disewa,dalam_perbaikan',
            'facilities' => 'array',
            'facilities.*' => 'exists:facilities,id'
        ]);

        $start = (int) $validated['bulk_start'];
        $count = (int) $validated['bulk_count'];
        $prefix = trim($validated['bulk_prefix']);

        $roomNumbers = [];
        for ($i = 0; $i < $count; $i++) {
            $roomNumbers[] = $prefix . ($start + $i);
        }

        $existingRooms = $kos->rooms()->whereIn('room_number', $roomNumbers)->pluck('room_number')->toArray();
        if (!empty($existingRooms)) {
            return back()->with('error', 'Gagal: Nomor kamar (' . implode(', ', $existingRooms) . ') sudah ada di sistem. Silakan ganti awalan atau angka mulai.')->withInput();
        }

        DB::transaction(function () use ($validated, $request, $kos, $roomNumbers) {
            for ($i = 0; $i < count($roomNumbers); $i++) {
                $roomNumber = $roomNumbers[$i];
                
                $room = $kos->rooms()->create([
                    'name' => $validated['name'],
                    'room_number' => $roomNumber,
                    'description' => $validated['description'],
                    'price' => $validated['price'],
                    'price_period' => $validated['price_period'],
                    'capacity' => $validated['capacity'],
                    'status' => $validated['status'],
                ]);

                if ($request->has('facilities')) {
                    $room->facilities()->sync($request->facilities);
                }
            }
        });

        return back()->with('success', $validated['bulk_count'] . ' Kamar berhasil dibuat massal.');
    }

    public function update(Request $request, BoardingHouse $kos, Room $room)
    {
        if ($kos->admin_id !== auth()->id() || $room->boarding_house_id !== $kos->id) abort(403);
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

        if ($kos->rooms()->where('room_number', $validated['room_number'])->where('id', '!=', $room->id)->exists()) {
            return back()->withErrors(['room_number' => 'Nomor kamar ini sudah digunakan.'])->withInput();
        }

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
        if ($kos->admin_id !== auth()->id() || $room->boarding_house_id !== $kos->id) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }
        
        $activeTenants = Tenancy::where('room_id', $room->id)->where('status', 'aktif')->count();
        if ($activeTenants > 0) {
            return back()->with('error', 'Kamar tidak bisa dihapus karena masih ada penyewa aktif.');
        }

        // Ubah nomor kamar agar namanya bisa dipakai lagi untuk kamar baru (menghindari duplicate unique constraint)
        $room->room_number = $room->room_number . '-deleted-' . time();
        $room->save();
        $room->delete();
        
        return back()->with('success', 'Kamar berhasil dihapus.');
    }
}
