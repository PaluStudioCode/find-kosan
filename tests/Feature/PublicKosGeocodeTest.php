<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\PublicKosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicKosGeocodeTest extends TestCase
{
    public function test_it_returns_coordinates_from_the_geocoding_service(): void
    {
        Cache::flush();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '-0.8917',
                    'lon' => '119.8707',
                    'display_name' => 'Palu, Sulawesi Tengah, Indonesia',
                ],
            ]),
        ]);

        $response = app(PublicKosController::class)->geocode(
            Request::create('/kos/geocode', 'GET', ['query' => 'Palu']),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'latitude' => -0.8917,
            'longitude' => 119.8707,
            'display_name' => 'Palu, Sulawesi Tengah, Indonesia',
        ], $response->getData(true));

        Http::assertSentCount(1);
        $this->assertTrue(Route::has('public.kos.geocode'));
    }

    public function test_it_returns_not_found_when_the_location_does_not_exist(): void
    {
        Cache::flush();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $response = app(PublicKosController::class)->geocode(
            Request::create('/kos/geocode', 'GET', ['query' => 'Daerah Tidak Ada']),
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['message' => 'Lokasi tidak ditemukan.'], $response->getData(true));
    }
}
