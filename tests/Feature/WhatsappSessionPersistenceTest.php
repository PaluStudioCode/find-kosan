<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaSession;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsappSessionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_status_keeps_saved_session_when_wa_service_is_unavailable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        WaSession::create([
            'admin_id' => $admin->id,
            'status' => 'connected',
            'phone_number' => '6281234567890',
            'connected_at' => now(),
        ]);

        $this->mockUnavailableWhatsappService($admin->id);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.status'));

        $response->assertOk()->assertJson([
            'success' => false,
            'status' => 'connected',
            'phone_number' => '6281234567890',
        ]);
        $this->assertDatabaseHas('wa_sessions', [
            'admin_id' => $admin->id,
            'status' => 'connected',
        ]);
    }

    public function test_superadmin_status_keeps_saved_session_when_wa_service_is_unavailable(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        WaSession::create([
            'admin_id' => WaSession::SUPERADMIN_SESSION_ID,
            'status' => 'connected',
            'phone_number' => '6289876543210',
            'connected_at' => now(),
        ]);

        $this->mockUnavailableWhatsappService(WaSession::SUPERADMIN_SESSION_ID);

        $response = $this->actingAs($superAdmin)->get(route('superadmin.whatsapp.status'));

        $response->assertOk()->assertJson([
            'success' => false,
            'status' => 'connected',
            'phone_number' => '6289876543210',
        ]);
        $this->assertDatabaseHas('wa_sessions', [
            'admin_id' => WaSession::SUPERADMIN_SESSION_ID,
            'status' => 'connected',
        ]);
    }

    public function test_live_disconnected_status_updates_the_saved_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        WaSession::create([
            'admin_id' => $admin->id,
            'status' => 'connected',
            'phone_number' => '6281234567890',
            'connected_at' => now(),
        ]);

        $service = Mockery::mock(WhatsappService::class);
        $service->shouldReceive('getStatus')
            ->once()
            ->with($admin->id)
            ->andReturn([
                'success' => true,
                'status' => 'disconnected',
                'phoneNumber' => null,
                'pairingCode' => null,
                'qr' => null,
                'error' => null,
            ]);
        $this->app->instance(WhatsappService::class, $service);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.status'));

        $response->assertOk()->assertJson([
            'success' => true,
            'status' => 'disconnected',
        ]);
        $this->assertDatabaseHas('wa_sessions', [
            'admin_id' => $admin->id,
            'status' => 'disconnected',
        ]);
    }

    private function mockUnavailableWhatsappService(int $adminId): void
    {
        $service = Mockery::mock(WhatsappService::class);
        $service->shouldReceive('getStatus')
            ->once()
            ->with($adminId)
            ->andReturn([
                'success' => false,
                'status' => null,
                'phoneNumber' => null,
                'pairingCode' => null,
                'qr' => null,
                'error' => 'Connection refused',
            ]);
        $this->app->instance(WhatsappService::class, $service);
    }
}
