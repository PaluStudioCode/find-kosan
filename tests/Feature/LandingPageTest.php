<?php

namespace Tests\Feature;

use App\Models\BoardingHouse;
use App\Models\BoardingHouseReview;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_landing_page_uses_live_platform_statistics_and_public_reviews(): void
    {
        $owner = User::factory()->create([
            'role' => 'pemilik_kos',
            'status' => 'aktif',
        ]);
        $tenant = User::factory()->create([
            'role' => 'penyewa',
            'status' => 'aktif',
            'name' => 'Penyewa Landing',
        ]);

        $publishedKos = BoardingHouse::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'dipublikasikan',
            'name' => 'Kos Tampil',
            'latitude' => -5.1477,
            'longitude' => 119.4327,
        ]);
        $draftKos = BoardingHouse::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'draft',
        ]);

        Room::factory()->create([
            'boarding_house_id' => $publishedKos->id,
            'status' => 'tersedia',
        ]);
        Room::factory()->create([
            'boarding_house_id' => $publishedKos->id,
            'status' => 'terisi',
        ]);
        Room::factory()->create([
            'boarding_house_id' => $draftKos->id,
            'status' => 'tersedia',
        ]);

        BoardingHouseReview::create([
            'boarding_house_id' => $publishedKos->id,
            'user_id' => $tenant->id,
            'rating' => 5,
            'comment' => 'Lokasinya mudah dijangkau dan pengelola responsif.',
        ]);

        $expectedOwnerCount = User::where('role', 'pemilik_kos')
            ->where('status', 'aktif')
            ->count();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('stats.publishedKos', 1)
                ->where('stats.availableRooms', 1)
                ->where('stats.activeTenants', 1)
                ->where('stats.registeredOwners', $expectedOwnerCount)
                ->has('mapKos', 1)
                ->where('mapKos.0.name', 'Kos Tampil')
                ->has('featuredReviews', 1)
                ->where('featuredReviews.0.user.name', 'Penyewa Landing')
                ->where('featuredReviews.0.boarding_house.name', 'Kos Tampil')
            );
    }
}
