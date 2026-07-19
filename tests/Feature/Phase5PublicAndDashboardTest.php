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
            ->has('boardingHouses.data', 1)
            ->where('boardingHouses.data.0.id', $publishedKos->id)
        );
    }

    public function test_public_kos_detail_returns_404_if_not_published()
    {
        $draftKos = BoardingHouse::factory()->create(['status' => 'draft']);

        $response = $this->get('/kos/' . $draftKos->id);
        
        $response->assertStatus(404);
    }

    public function test_tenant_dashboard_shows_own_data()
    {
        $tenant1 = User::factory()->create(['role' => 'penyewa', 'status' => 'aktif']);
        $tenant2 = User::factory()->create(['role' => 'penyewa', 'status' => 'aktif']);
        
        $owner = User::factory()->create(['role' => 'pemilik_kos', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['owner_id' => $owner->id]);
        $room = Room::factory()->create(['boarding_house_id' => $kos->id, 'price_period' => 'bulanan']);
        $tenancy = Tenancy::factory()->create(['room_id' => $room->id, 'tenant_id' => $tenant1->id, 'owner_id' => $owner->id, 'boarding_house_id' => $kos->id]);
        
        Invoice::factory()->create([
            'tenant_id' => $tenant1->id,
            'owner_id' => $owner->id,
            'tenancy_id' => $tenancy->id,
            'status' => 'belum_dibayar',
            'amount' => 1000000,
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'due_date' => now()->addDays(3)
        ]);

        $response = $this->actingAs($tenant1)->get('/tenant/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tenant/Dashboard')
            ->where('metrics.unpaidInvoices', 1)
        );

        $response2 = $this->actingAs($tenant2)->get('/tenant/dashboard');
        $response2->assertInertia(fn (Assert $page) => $page
            ->component('Tenant/Dashboard')
            ->where('metrics.unpaidInvoices', 0)
        );
    }

    public function test_owner_dashboard_shows_own_data()
    {
        $owner1 = User::factory()->create(['role' => 'pemilik_kos', 'status' => 'aktif']);
        $owner2 = User::factory()->create(['role' => 'pemilik_kos', 'status' => 'aktif']);

        BoardingHouse::factory()->count(2)->create(['owner_id' => $owner1->id]);
        BoardingHouse::factory()->count(1)->create(['owner_id' => $owner2->id]);

        $response = $this->actingAs($owner1)->get('/owner/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Owner/Dashboard')
            ->where('metrics.totalKos', 2)
        );
    }
    public function test_admin_dashboard_shows_data()
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'aktif']);
        $owner = User::factory()->create(['role' => 'pemilik_kos', 'status' => 'aktif']);

        BoardingHouse::factory()->create(['status' => 'menunggu_verifikasi', 'owner_id' => $owner->id]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('metrics.pendingKosVerifications', 1)
        );
    }
}
