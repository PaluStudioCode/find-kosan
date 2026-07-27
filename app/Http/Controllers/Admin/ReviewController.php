<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouseReview;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $adminId = auth()->id();
        $kosFilter = $request->input('kos_id');
        $ratingFilter = $request->input('rating');

        $query = BoardingHouseReview::whereHas('boardingHouse', function ($q) use ($adminId) {
                $q->where('admin_id', $adminId);
            })
            ->with(['user:id,name,email', 'boardingHouse:id,name']);

        if ($kosFilter) {
            $query->where('boarding_house_id', $kosFilter);
        }

        if ($ratingFilter) {
            $query->where('rating', $ratingFilter);
        }

        $reviews = $query->latest()->paginate(10)->withQueryString();

        $kosList = \App\Models\BoardingHouse::where('admin_id', $adminId)->select('id', 'name')->get();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'kosList' => $kosList,
            'filters' => [
                'kos_id' => $kosFilter,
                'rating' => $ratingFilter,
            ]
        ]);
    }
}
