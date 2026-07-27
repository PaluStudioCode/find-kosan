<?php

namespace Tests\Feature;

use App\Models\BoardingHouse;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Tenancy;

class Phase6PropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_owner_can_create_kos()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);

        $response = $this->actingAs($admin)->post('/admin/kos', [
            'name' => 'Kos Baru',
            'description' => 'Deskripsi kos',
            'address' => 'Jl. Baru No. 1',
            'city' => 'Jakarta',
            'payment_proof_required' => true,
        ]);

        $response->assertRedirect('/admin/kos');
        $this->assertDatabaseHas('boarding_houses', [
            'name' => 'Kos Baru',
            'admin_id' => $admin->id,
            'status' => 'draft',
        ]);
    }

    public function test_owner_can_update_kos_and_trigger_shadow_revision()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['admin_id' => $admin->id, 'status' => 'dipublikasikan']);

        $response = $this->actingAs($admin)->put("/admin/kos/{$kos->id}", [
            'name' => 'Kos Berubah',
            'description' => 'Deskripsi',
            'address' => 'Alamat',
            'payment_proof_required' => true,
        ]);

        $response->assertSessionHas('success');
        
        $kos->refresh();
        $this->assertEquals('dipublikasikan', $kos->status);
        $this->assertNotNull($kos->pending_revisions);
        $this->assertEquals('Kos Berubah', $kos->pending_revisions['name']);
        
        $this->assertNotEquals('Kos Berubah', $kos->name);
    }

    public function test_owner_can_create_and_delete_room()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['admin_id' => $admin->id]);

        // Create Room
        $response = $this->actingAs($admin)->post("/admin/kos/{$kos->id}/rooms", [
            'name' => 'Kamar VIP',
            'room_number' => 'A1',
            'description' => 'Desc',
            'price' => 1000000,
            'price_period' => 'bulanan',
            'capacity' => 2,
            'status' => 'tersedia',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('rooms', ['name' => 'Kamar VIP', 'boarding_house_id' => $kos->id]);

        $room = Room::where('name', 'Kamar VIP')->first();

        // Delete Room
        $response = $this->actingAs($admin)->delete("/admin/kos/{$kos->id}/rooms/{$room->id}");
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('rooms', ['id' => $room->id]);
    }

    public function test_owner_cannot_reduce_capacity_below_active_tenants()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $user1 = User::factory()->create(['role' => 'user', 'status' => 'aktif']);
        $user2 = User::factory()->create(['role' => 'user', 'status' => 'aktif']);

        $kos = BoardingHouse::factory()->create(['admin_id' => $admin->id]);
        $room = Room::factory()->create(['boarding_house_id' => $kos->id, 'capacity' => 2]);

        Tenancy::factory()->create(['room_id' => $room->id, 'user_id' => $user1->id, 'status' => 'aktif', 'boarding_house_id' => $kos->id, 'admin_id' => $admin->id]);
        Tenancy::factory()->create(['room_id' => $room->id, 'user_id' => $user2->id, 'status' => 'aktif', 'boarding_house_id' => $kos->id, 'admin_id' => $admin->id]);

        $response = $this->actingAs($admin)->put("/admin/kos/{$kos->id}/rooms/{$room->id}", [
            'name' => 'Kamar VIP',
            'room_number' => 'A1',
            'description' => 'Desc',
            'price' => 1000000,
            'price_period' => 'bulanan',
            'capacity' => 1,
            'status' => 'tersedia',
        ]);

        $response->assertSessionHasErrors('capacity');
    }

    public function test_owner_can_upload_legal_doc_and_request_verification()
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['admin_id' => $admin->id, 'status' => 'draft']);

        $response = $this->actingAs($admin)->post("/admin/kos/{$kos->id}/verify");
        $response->assertSessionHas('error'); 

        $file = UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf');
        $this->actingAs($admin)->post("/admin/kos/{$kos->id}/legal-documents", [
            'document_type' => 'identitas_pemilik_pengelola',
            'file' => $file
        ]);

        $response = $this->actingAs($admin)->post("/admin/kos/{$kos->id}/verify");
        $response->assertSessionHas('error'); 

        $photo = UploadedFile::fake()->image('kos.jpg');
        $this->actingAs($admin)->post("/admin/kos/{$kos->id}/photos", [
            'photos' => [$photo]
        ]);

        $response = $this->actingAs($admin)->post("/admin/kos/{$kos->id}/verify");
        $response->assertSessionHas('success');
        $this->assertEquals('menunggu_verifikasi', $kos->refresh()->status);
    }

    public function test_super_admin_can_approve_kos()
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'aktif']);
        $kos = BoardingHouse::factory()->create(['status' => 'menunggu_verifikasi']);

        $response = $this->actingAs($admin)->post("/superadmin/verifications/{$kos->id}/approve");
        $response->assertRedirect('/superadmin/verifications');

        $kos->refresh();
        $this->assertEquals('dipublikasikan', $kos->status);
        $this->assertNotNull($kos->verified_at);
        $this->assertEquals($admin->id, $kos->verified_by);
    }
}
