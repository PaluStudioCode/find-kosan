<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\BoardingHouseReview;
use Illuminate\Http\Request;

class KosReviewController extends Controller
{
    public function store(Request $request, BoardingHouse $kos)
    {
        abort_unless($kos->status === 'dipublikasikan', 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'rating.required' => 'Pilih rating terlebih dahulu.',
            'rating.between' => 'Rating harus antara 1 sampai 5.',
            'comment.required' => 'Komentar wajib diisi.',
            'comment.min' => 'Komentar minimal 5 karakter.',
            'comment.max' => 'Komentar maksimal 1000 karakter.',
        ]);

        $review = BoardingHouseReview::updateOrCreate(
            [
                'boarding_house_id' => $kos->id,
                'user_id' => $request->user()->id,
            ],
            $validated,
        );

        return back()->with(
            'success',
            $review->wasRecentlyCreated
                ? 'Rating dan komentar berhasil dikirim.'
                : 'Rating dan komentar berhasil diperbarui.',
        );
    }
}
