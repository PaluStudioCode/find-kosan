<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouseReview;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index()
    {
        $ownerId = auth()->id();

        $reviews = BoardingHouseReview::whereHas('boardingHouse', function ($query) use ($ownerId) {
                $query->where('owner_id', $ownerId);
            })
            ->with(['user:id,name,email,avatar_path', 'boardingHouse:id,name'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Owner/Reviews/Index', [
            'reviews' => $reviews
        ]);
    }
}
