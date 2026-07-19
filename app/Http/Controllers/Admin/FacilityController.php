<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class FacilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Facility::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $facilities = $query->orderBy('type')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Facilities/Index', [
            'facilities' => $facilities,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:kos,kamar',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        // Check unique constraint for name and type combination
        $exists = Facility::where('name', $validated['name'])
            ->where('type', $validated['type'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Fasilitas dengan nama dan tipe ini sudah ada.']);
        }

        Facility::create($validated);

        return back()->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:kos,kamar',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        // Check unique constraint excluding current facility
        $exists = Facility::where('name', $validated['name'])
            ->where('type', $validated['type'])
            ->where('id', '!=', $facility->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Fasilitas dengan nama dan tipe ini sudah ada.']);
        }

        $facility->update($validated);

        return back()->with('success', 'Fasilitas berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Facility $facility)
    {
        $facility->delete();

        return back()->with('success', 'Fasilitas berhasil dihapus.');
    }
}
