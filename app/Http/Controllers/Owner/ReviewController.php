<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouseReview;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = auth()->id();
        $kosFilter = $request->input('kos_id');
        $ratingFilter = $request->input('rating');

        $query = BoardingHouseReview::whereHas('boardingHouse', function ($q) use ($ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->with(['user:id,name,email', 'boardingHouse:id,name']);

        if ($kosFilter) {
            $query->where('boarding_house_id', $kosFilter);
        }

        if ($ratingFilter) {
            $query->where('rating', $ratingFilter);
        }

        $reviews = $query->latest()->paginate(10)->withQueryString();

        $kosList = \App\Models\BoardingHouse::where('owner_id', $ownerId)->select('id', 'name')->get();

        return Inertia::render('Owner/Reviews/Index', [
            'reviews' => $reviews,
            'kosList' => $kosList,
            'filters' => [
                'kos_id' => $kosFilter,
                'rating' => $ratingFilter,
            ]
        ]);
    }
}
