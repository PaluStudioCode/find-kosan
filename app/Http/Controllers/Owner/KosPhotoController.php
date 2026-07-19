<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\BoardingHousePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KosPhotoController extends Controller
{
    public function store(Request $request, BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:2048',
        ]);

        $currentCount = $kos->photos()->count();
        $maxSort = $kos->photos()->max('sort_order') ?? 0;

        foreach ($request->file('photos') as $index => $photo) {
            $path = $photo->store('kos_photos', 'public');
            $isMain = ($currentCount === 0 && $index === 0);

            $kos->photos()->create([
                'file_path' => '/storage/' . $path,
                'is_primary' => $isMain,
                'sort_order' => $maxSort + $index + 1,
            ]);
        }

        $count = count($request->file('photos'));
        return back()->with('success', $count . ' foto kos berhasil diunggah.');
    }

    public function update(Request $request, BoardingHouse $kos, BoardingHousePhoto $photo)
    {
        if ($kos->owner_id !== auth()->id() || $photo->boarding_house_id !== $kos->id) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }
        
        $kos->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);

        return back()->with('success', 'Foto utama berhasil diubah.');
    }

    public function destroy(BoardingHouse $kos, BoardingHousePhoto $photo)
    {
        if ($kos->owner_id !== auth()->id() || $photo->boarding_house_id !== $kos->id) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }
        
        $path = str_replace('/storage/', '', $photo->file_path);
        // Hapus fisik file di storage
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        
        // Hapus permanen dari database
        $photo->forceDelete();

        if ($photo->is_primary) {
            $first = $kos->photos()->first();
            if ($first) {
                $first->update(['is_primary' => true]);
            }
        }

        return back()->with('success', 'Foto kos berhasil dihapus.');
    }
}
