<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class PublicKosController extends Controller
{
    public function geocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:120'],
        ]);

        $query = trim($validated['query']);
        $cacheKey = 'public-kos-geocode:'.md5(mb_strtolower($query));

        try {
            $result = Cache::remember($cacheKey, now()->addDay(), function () use ($query) {
                $response = Http::acceptJson()
                    ->withUserAgent(config('app.name', 'Find Kosan').'/1.0 ('.config('app.url').')')
                    ->timeout(8)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'format' => 'jsonv2',
                        'q' => $query.', Indonesia',
                        'limit' => 1,
                        'countrycodes' => 'id',
                        'accept-language' => 'id',
                    ]);

                if (! $response->successful()) {
                    return null;
                }

                $location = collect($response->json())->first();

                if (! is_array($location) || ! isset($location['lat'], $location['lon'])) {
                    return [];
                }

                return [
                    'latitude' => (float) $location['lat'],
                    'longitude' => (float) $location['lon'],
                    'display_name' => $location['display_name'] ?? $query,
                ];
            });
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Layanan pencarian lokasi sedang tidak tersedia.',
            ], 503);
        }

        if ($result === null) {
            return response()->json([
                'message' => 'Layanan pencarian lokasi sedang tidak tersedia.',
            ], 503);
        }

        if ($result === []) {
            return response()->json([
                'message' => 'Lokasi tidak ditemukan.',
            ], 404);
        }

        return response()->json($result);
    }

    public function index(Request $request)
    {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        $radius = (float) $request->query('radius', 5);
        $highlightKosId = $request->query('highlight_kos_id');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $facilitiesQuery = $request->query('facilities'); // array of facility IDs

        $query = BoardingHouse::with([
            'facilities',
            'photos' => function ($q) {
                $q->where('is_primary', true);
            },
        ])->withMin('rooms', 'price') // Include minimum room price
          ->where('status', 'dipublikasikan')
          ->whereNotNull('latitude')
          ->whereNotNull('longitude');

        // Apply Price Filter
        if ($minPrice || $maxPrice) {
            $query->whereHas('rooms', function ($q) use ($minPrice, $maxPrice) {
                $q->where('status', 'tersedia'); // Only consider available rooms for price filter
                if ($minPrice) {
                    $q->where('price', '>=', $minPrice);
                }
                if ($maxPrice) {
                    $q->where('price', '<=', $maxPrice);
                }
            });
        }

        // Apply Facilities Filter (Checks both Kos Facilities and Available Room Facilities)
        if ($facilitiesQuery && is_array($facilitiesQuery) && count($facilitiesQuery) > 0) {
            foreach ($facilitiesQuery as $facilityId) {
                $query->where(function ($subQuery) use ($facilityId) {
                    $subQuery->whereHas('facilities', function ($q) use ($facilityId) {
                        $q->where('facilities.id', $facilityId);
                    })->orWhereHas('rooms', function ($q) use ($facilityId) {
                        $q->where('status', 'tersedia')
                          ->whereHas('facilities', function ($q2) use ($facilityId) {
                              $q2->where('facilities.id', $facilityId);
                          });
                    });
                });
            }
        }

        if ($lat && $lng) {
            // SQL Haversine Formula
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
            
            $query->selectRaw("boarding_houses.*, {$haversine} AS distance", [$lat, $lng, $lat]);

            if ($radius < 51) {
                $query->having('distance', '<=', $radius);
            }
            $query->orderBy('distance');
        } else {
            $query->latest();
        }

        // Limit the result to avoid overloading the browser map
        $allKos = $query->limit(100)->get();

        // Get essential facilities for filter master data
        $essentialFacilityNames = ['WiFi / Internet', 'Kamar Mandi Dalam', 'AC', 'Parkir Mobil'];
        $filterFacilities = \App\Models\Facility::whereIn('name', $essentialFacilityNames)
            ->where('status', 'aktif')
            ->get(['id', 'name']);

        return Inertia::render('Public/Kos/Index', [
            'allKos' => $allKos,
            'filterFacilities' => $filterFacilities,
            'filters' => [
                'lat' => $lat,
                'lng' => $lng,
                'radius' => $radius,
                'highlight_kos_id' => $highlightKosId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'facilities' => $facilitiesQuery ?? [],
            ]
        ]);
    }

    public function show(Request $request, BoardingHouse $kos)
    {
        if ($kos->status !== 'dipublikasikan') {
            // Izinkan Admin melihat kos yang berstatus selain dipublikasikan (seperti suspended/menunggu_verifikasi)
            if (! $request->user() || $request->user()->role !== 'admin') {
                abort(404);
            }
        }

        return Inertia::render('Public/Kos/Show', [
            'kos' => Inertia::defer(function () use ($kos) {
                $kos->load([
                    'facilities',
                    'rules',
                    'rooms' => function ($q) {
                        $q->where('status', 'tersedia')->with('facilities');
                    },
                    'photos' => function ($q) {
                        $q->orderBy('is_primary', 'desc');
                    },
                    'admin:id,name,whatsapp_number,email',
                ]);
                return $kos;
            }),
            'reviews' => Inertia::defer(function () use ($kos) {
                return $kos->reviews()
                    ->with('user:id,name')
                    ->latest()
                    ->paginate(10, ['*'], 'reviews_page')
                    ->withQueryString();
            }),
            'reviewSummary' => Inertia::defer(function () use ($kos) {
                $reviewSummary = $kos->reviews()
                    ->selectRaw('COUNT(*) as total, AVG(rating) as average')
                    ->first();
                return [
                    'average' => $reviewSummary->average
                        ? round((float) $reviewSummary->average, 1)
                        : null,
                    'total' => (int) $reviewSummary->total,
                ];
            }),
            'currentReview' => Inertia::defer(function () use ($request, $kos) {
                if ($request->user()?->role === 'user') {
                    return $kos->reviews()
                        ->where('user_id', $request->user()->id)
                        ->first();
                }
                return null;
            }),
            'hasRented' => Inertia::defer(function () use ($request, $kos) {
                if ($request->user()?->role === 'user') {
                    return \App\Models\Tenancy::where('user_id', $request->user()->id)
                        ->where('boarding_house_id', $kos->id)
                        ->exists();
                }
                return false;
            }),
        ]);
    }
}
