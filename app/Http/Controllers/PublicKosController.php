<?php

namespace App\Http\Controllers;

use App\Models\BoardingHouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicKosController extends Controller
{
    public function index(Request $request)
    {
        $allKos = BoardingHouse::with([
            'facilities',
            'photos' => function ($q) {
                $q->where('is_primary', true);
            },
        ])->where('status', 'dipublikasikan')->latest()->get();

        return Inertia::render('Public/Kos/Index', [
            'allKos' => $allKos,
        ]);
    }

    public function show(Request $request, BoardingHouse $kos)
    {
        if ($kos->status !== 'dipublikasikan') {
            abort(404);
        }

        $kos->load([
            'facilities',
            'rooms' => function ($q) {
                $q->where('status', 'tersedia')->with('facilities', 'photos');
            },
            'photos' => function ($q) {
                $q->orderBy('is_primary', 'desc');
            },
            'owner:id,name,whatsapp_number,email',
        ]);

        $reviews = $kos->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10, ['*'], 'reviews_page')
            ->withQueryString();

        $reviewSummary = $kos->reviews()
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        $currentReview = null;
        if ($request->user()?->role === 'penyewa') {
            $currentReview = $kos->reviews()
                ->where('user_id', $request->user()->id)
                ->first();
        }

        return Inertia::render('Public/Kos/Show', [
            'kos' => $kos,
            'reviews' => $reviews,
            'reviewSummary' => [
                'average' => $reviewSummary->average
                    ? round((float) $reviewSummary->average, 1)
                    : null,
                'total' => (int) $reviewSummary->total,
            ],
            'currentReview' => $currentReview,
        ]);
    }
}
