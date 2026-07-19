<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Facility;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class KosController extends Controller
{
    public function index()
    {
        $boardingHouses = BoardingHouse::where('owner_id', auth()->id())
            ->with(['photos' => function($q) { $q->where('is_primary', true); }])
            ->withCount('rooms')
            ->latest()
            ->paginate(10);

        return Inertia::render('Owner/Kos/Index', [
            'boardingHouses' => $boardingHouses
        ]);
    }

    public function create()
    {
        return Inertia::render('Owner/Kos/Form', [
            'facilities' => Facility::where('type', 'kos')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'required|string',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'subdistrict' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'public_contact_name' => 'nullable|string|max:150',
            'public_contact_whatsapp_number' => 'nullable|string|max:30',
            'payment_instructions' => 'nullable|string',
            'payment_proof_required' => 'boolean',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id'
        ]);

        $facilityIds = $validated['facilities'] ?? [];
        unset($validated['facilities']);

        $validated['owner_id'] = auth()->id();
        $validated['status'] = 'draft';

        DB::transaction(function () use ($validated, $facilityIds) {
            $kos = BoardingHouse::create($validated);
            $kos->facilities()->sync($facilityIds);
        });

        return redirect()->route('owner.kos.index')->with('success', 'Data kos berhasil disimpan sebagai draft.');
    }

    public function show(BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        
        $kos->load([
            'facilities', 
            'rooms.facilities', 'rooms.photos',
            'photos' => function($q) {
                $q->orderBy('is_primary', 'desc');
            }, 
            'legalDocuments'
        ]);

        return Inertia::render('Owner/Kos/Show', [
            'kos' => $kos,
            'kosFacilitiesList' => Facility::where('type', 'kos')->get(),
            'roomFacilitiesList' => Facility::where('type', 'kamar')->get()
        ]);
    }

    public function update(Request $request, BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'required|string',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'subdistrict' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'public_contact_name' => 'nullable|string|max:150',
            'public_contact_whatsapp_number' => 'nullable|string|max:30',
            'payment_instructions' => 'nullable|string',
            'payment_proof_required' => 'boolean',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id'
        ]);

        $facilityIds = $validated['facilities'] ?? [];
        unset($validated['facilities']);

        DB::transaction(function () use ($validated, $facilityIds, $kos) {
            if ($kos->status === 'dipublikasikan') {
                $kos->pending_revisions = $validated;
                $kos->save();
            } else {
                $kos->update($validated);
                $kos->facilities()->sync($facilityIds);
            }
        });

        return back()->with('success', 'Data kos berhasil diperbarui.');
    }

    public function requestVerification(BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        
        if ($kos->legalDocuments()->count() === 0) {
            return back()->with('error', 'Minimal satu dokumen legalitas diperlukan untuk mengajukan verifikasi.');
        }

        if ($kos->photos()->count() === 0) {
            return back()->with('error', 'Minimal satu foto kos diperlukan.');
        }

        if ($kos->status === 'dipublikasikan' && empty($kos->pending_revisions)) {
            return back()->with('error', 'Kos sudah dipublikasikan.');
        }

        $kos->status = 'menunggu_verifikasi';
        $kos->save();

        return back()->with('success', 'Pengajuan verifikasi berhasil dikirim.');
    }

    public function destroy(BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Properti tidak dapat dihapus karena sedang dalam proses peninjauan.');
        }

        $kos->delete();
        return redirect()->route('owner.kos.index')->with('success', 'Kos berhasil dihapus.');
    }

    public function uploadQris(Request $request, BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        $request->validate(['qris_image' => 'required|image|max:2048']);

        if ($kos->payment_qris_image_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($kos->payment_qris_image_path);
        }

        $path = $request->file('qris_image')->store('qris', 'local');
        $kos->update(['payment_qris_image_path' => $path]);

        return back()->with('success', 'QRIS berhasil diunggah.');
    }
    public function deleteQris(BoardingHouse $kos)
    {
        if ($kos->owner_id !== auth()->id()) abort(403);
        if ($kos->status === 'menunggu_verifikasi') {
            return back()->with('error', 'Data tidak dapat diubah karena sedang dalam proses peninjauan.');
        }

        if ($kos->payment_qris_image_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($kos->payment_qris_image_path);
            $kos->update(['payment_qris_image_path' => null]);
        }

        return back()->with('success', 'QRIS berhasil dihapus.');
    }
}
