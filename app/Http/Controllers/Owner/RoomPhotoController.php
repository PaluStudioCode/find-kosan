<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Room;
use App\Models\RoomPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomPhotoController extends Controller
{
    public function store(Request $request, BoardingHouse $kos, Room $room)
    {
        if ($kos->owner_id !== auth()->id() || $room->boarding_house_id !== $kos->id) abort(403);

        $request->validate(['photo' => 'required|image|max:2048']);
        $path = $request->file('photo')->store('room_photos', 'public');

        $room->photos()->create([
            'image_path' => '/storage/' . $path,
        ]);

        return back()->with('success', 'Foto kamar berhasil diunggah.');
    }

    public function destroy(BoardingHouse $kos, Room $room, RoomPhoto $photo)
    {
        if ($kos->owner_id !== auth()->id() || $room->boarding_house_id !== $kos->id || $photo->room_id !== $room->id) abort(403);
        
        $path = str_replace('/storage/', '', $photo->image_path);
        Storage::disk('public')->delete($path);
        $photo->delete();

        return back()->with('success', 'Foto kamar berhasil dihapus.');
    }
}
