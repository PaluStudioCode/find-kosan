<?php

namespace App\Http\Controllers;

use App\Models\BoardingHouse;
use App\Models\BoardingHouseReview;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function index()
    {
        $mapKos = BoardingHouse::query()
            ->where('status', 'dipublikasikan')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->limit(50)
            ->get([
                'id',
                'name',
                'city',
                'district',
                'latitude',
                'longitude',
            ]);

        if ($mapKos->isNotEmpty()) {
            $anchor = $mapKos->first();

            $mapKos = $mapKos
                ->map(fn (BoardingHouse $kos) => [
                    'id' => $kos->id,
                    'name' => $kos->name,
                    'city' => $kos->city,
                    'district' => $kos->district,
                    'latitude' => (float) $kos->latitude,
                    'longitude' => (float) $kos->longitude,
                    'distance' => $this->distanceInKilometers(
                        (float) $anchor->latitude,
                        (float) $anchor->longitude,
                        (float) $kos->latitude,
                        (float) $kos->longitude,
                    ),
                ])
                ->filter(fn (array $kos) => $kos['distance'] <= 25)
                ->sortBy('distance')
                ->take(8)
                ->values();
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'stats' => [
                'publishedKos' => BoardingHouse::where('status', 'dipublikasikan')->count(),
                'availableRooms' => Room::where('status', 'tersedia')
                    ->whereHas('boardingHouse', fn ($query) => $query->where('status', 'dipublikasikan'))
                    ->count(),
                'activeTenants' => User::where('role', 'penyewa')->where('status', 'aktif')->count(),
                'registeredOwners' => User::where('role', 'pemilik_kos')->where('status', 'aktif')->count(),
            ],
            'mapKos' => $mapKos,
            'featuredReviews' => BoardingHouseReview::query()
                ->whereHas('boardingHouse', fn ($query) => $query->where('status', 'dipublikasikan'))
                ->with([
                    'user:id,name',
                    'boardingHouse:id,name,city',
                ])
                ->latest()
                ->limit(6)
                ->get([
                    'id',
                    'boarding_house_id',
                    'user_id',
                    'rating',
                    'comment',
                    'created_at',
                ]),
        ]);
    }

    private function distanceInKilometers(
        float $latitudeFrom,
        float $longitudeFrom,
        float $latitudeTo,
        float $longitudeTo,
    ): float {
        $earthRadius = 6371;
        $latitudeDelta = deg2rad($latitudeTo - $latitudeFrom);
        $longitudeDelta = deg2rad($longitudeTo - $longitudeFrom);

        $calculation = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeFrom))
            * cos(deg2rad($latitudeTo))
            * sin($longitudeDelta / 2) ** 2;
        $calculation = min(1, max(0, $calculation));

        return $earthRadius * 2 * atan2(sqrt($calculation), sqrt(1 - $calculation));
    }
}
