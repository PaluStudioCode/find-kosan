<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
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

        $kosList = BoardingHouse::where('admin_id', $adminId)->select('id', 'name')->get();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => Inertia::defer(function () use ($adminId, $kosFilter, $ratingFilter) {
                $query = BoardingHouseReview::whereHas('boardingHouse', function ($q) use ($adminId) {
                    $q->where('admin_id', $adminId);
                })->with(['user:id,name,email', 'boardingHouse:id,name']);

                if ($kosFilter) {
                    $query->where('boarding_house_id', $kosFilter);
                }

                if ($ratingFilter) {
                    $query->where('rating', $ratingFilter);
                }

                return $query->latest()->paginate(6)->withQueryString();
            }),
            'kosList' => $kosList,
            'filters' => [
                'kos_id' => $kosFilter,
                'rating' => $ratingFilter,
            ],
        ]);
    }
}
