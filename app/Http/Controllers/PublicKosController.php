<?php

namespace App\Http\Controllers;

use App\Models\BoardingHouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicKosController extends Controller
{
    public function index(Request $request)
    {
        $query = BoardingHouse::with([
            'facilities', 
            'photos' => function($q) {
                $q->where('is_primary', true);
            }
        ])->where('status', 'dipublikasikan');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('district', 'like', "%{$search}%");
            });
        }
        
        $boardingHouses = $query->latest()->paginate(12)->withQueryString();

        return Inertia::render('Public/Kos/Index', [
            'boardingHouses' => $boardingHouses,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(BoardingHouse $kos)
    {
        if ($kos->status !== 'dipublikasikan') {
            abort(404);
        }

        $kos->load([
            'facilities', 
            'rooms' => function($q) {
                $q->where('status', 'tersedia')->with('facilities', 'photos');
            }, 
            'photos' => function($q) {
                $q->orderBy('is_primary', 'desc');
            }, 
            'owner:id,name,whatsapp_number,email'
        ]);

        return Inertia::render('Public/Kos/Show', [
            'kos' => $kos
        ]);
    }
}
