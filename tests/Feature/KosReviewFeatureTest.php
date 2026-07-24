<?php

namespace Tests\Feature;

use App\Models\BoardingHouse;
use App\Models\BoardingHouseReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KosReviewFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_can_see_rating_summary_and_comments_on_kos_detail(): void
    {
        $kos = BoardingHouse::factory()->create(['status' => 'dipublikasikan']);
        $reviewer = User::factory()->create(['name' => 'Penyewa Lama']);

        BoardingHouseReview::create([
            'boarding_house_id' => $kos->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => 'Lokasinya strategis dan kamarnya bersih.',
        ]);

        $this->get(route('public.kos.show', $kos))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Kos/Show')
                ->where('reviewSummary.average', 4)
                ->where('reviewSummary.total', 1)
                ->has('reviews.data', 1)
                ->where('reviews.data.0.user.name', 'Penyewa Lama')
                ->where('reviews.data.0.comment', 'Lokasinya strategis dan kamarnya bersih.')
                ->where('currentReview', null)
            );
    }

    public function test_tenant_can_create_and_then_update_one_review_per_kos(): void
    {
        $tenant = User::factory()->create(['role' => 'penyewa', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['status' => 'dipublikasikan']);

        $this->actingAs($tenant)
            ->post(route('tenant.kos.reviews.store', $kos), [
                'rating' => 3,
                'comment' => 'Kos cukup nyaman untuk ditinggali.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Rating dan komentar berhasil dikirim.');

        $this->actingAs($tenant)
            ->post(route('tenant.kos.reviews.store', $kos), [
                'rating' => 5,
                'comment' => 'Setelah beberapa waktu, pelayanannya sangat baik.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Rating dan komentar berhasil diperbarui.');

        $this->assertDatabaseCount('boarding_house_reviews', 1);
        $this->assertDatabaseHas('boarding_house_reviews', [
            'boarding_house_id' => $kos->id,
            'user_id' => $tenant->id,
            'rating' => 5,
            'comment' => 'Setelah beberapa waktu, pelayanannya sangat baik.',
        ]);
    }

    public function test_review_requires_valid_rating_and_comment(): void
    {
        $tenant = User::factory()->create(['role' => 'penyewa', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['status' => 'dipublikasikan']);

        $this->actingAs($tenant)
            ->post(route('tenant.kos.reviews.store', $kos), [
                'rating' => 6,
                'comment' => 'Buruk',
            ])
            ->assertSessionHasErrors(['rating']);

        $this->assertDatabaseCount('boarding_house_reviews', 0);
    }

    public function test_guest_must_login_and_owner_cannot_submit_a_review(): void
    {
        $kos = BoardingHouse::factory()->create(['status' => 'dipublikasikan']);

        $this->post(route('tenant.kos.reviews.store', $kos), [
            'rating' => 5,
            'comment' => 'Komentar dari guest.',
        ])->assertRedirect(route('login'));

        $owner = User::factory()->create(['role' => 'pemilik_kos', 'status' => 'aktif']);

        $this->actingAs($owner)
            ->post(route('tenant.kos.reviews.store', $kos), [
                'rating' => 5,
                'comment' => 'Komentar dari pemilik.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('boarding_house_reviews', 0);
    }

    public function test_review_cannot_be_submitted_for_unpublished_kos(): void
    {
        $tenant = User::factory()->create(['role' => 'penyewa', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['status' => 'draft']);

        $this->actingAs($tenant)
            ->post(route('tenant.kos.reviews.store', $kos), [
                'rating' => 5,
                'comment' => 'Kos ini belum dipublikasikan.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('boarding_house_reviews', 0);
    }
}
