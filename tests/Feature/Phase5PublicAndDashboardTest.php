<?php

namespace Tests\Feature;

use App\Models\BoardingHouse;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase5PublicAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_kos_index_only_shows_published_kos()
    {
        $publishedKos = BoardingHouse::factory()->create(['status' => 'dipublikasikan']);
        $draftKos = BoardingHouse::factory()->create(['status' => 'draft']);

        $response = $this->get('/kos');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Kos/Index')
            ->has('allKos', 1)
            ->where('allKos.0.id', $publishedKos->id)
        );
    }

    public function test_public_kos_detail_returns_404_if_not_published()
    {
        $draftKos = BoardingHouse::factory()->create(['status' => 'draft']);

        $response = $this->get('/kos/' . $draftKos->id);
        
        $response->assertStatus(404);
    }

    public function test_owner_dashboard_shows_own_data()
    {
        $admin1 = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $admin2 = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);

        BoardingHouse::factory()->count(2)->create(['admin_id' => $admin1->id]);
        BoardingHouse::factory()->count(1)->create(['admin_id' => $admin2->id]);

        $response = $this->actingAs($admin1)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics.totalRooms')
        );
    }
    public function test_admin_dashboard_shows_data()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'status' => 'aktif']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);

        BoardingHouse::factory()->create(['status' => 'menunggu_verifikasi', 'admin_id' => $admin->id]);

        $response = $this->actingAs($superAdmin)->get('/superadmin/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('SuperAdmin/Dashboard')
            ->where('metrics.pendingKosVerifications', 1)
        );
    }
}
