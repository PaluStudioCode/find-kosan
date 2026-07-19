<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BoardingHouse;
use App\Models\UserActivationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase3AuthAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_fails_if_account_not_active()
    {
        $user = User::factory()->create([
            'status' => 'menunggu_aktivasi',
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertInvalid(['email']);
        $this->assertGuest();
    }

    public function test_login_success_if_account_active()
    {
        $user = User::factory()->create([
            'status' => 'aktif',
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_redirects_per_role()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'pemilik_kos']);
        $tenant = User::factory()->create(['role' => 'penyewa']);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs($owner)->get('/dashboard')->assertRedirect('/owner/dashboard');
        $this->actingAs($tenant)->get('/dashboard')->assertRedirect('/tenant/dashboard');
    }

    public function test_registration_creates_pemilik_kos()
    {
        $response = $this->post('/register', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'whatsapp_number' => '6281234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => 'pemilik_kos',
            'status' => 'aktif'
        ]);
    }

    public function test_role_middleware_returns_403()
    {
        $tenant = User::factory()->create(['role' => 'penyewa']);
        $this->actingAs($tenant)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_policy_denies_access_to_other_owners_data()
    {
        $owner1 = User::factory()->create(['role' => 'pemilik_kos']);
        $owner2 = User::factory()->create(['role' => 'pemilik_kos']);

        $boardingHouse = BoardingHouse::create([
            'owner_id' => $owner1->id,
            'name' => 'Kos 1',
            'description' => 'Desc',
            'address' => 'Addr',
            'status' => 'draft'
        ]);

        // owner1 can update
        $this->assertTrue($owner1->can('update', $boardingHouse));
        
        // owner2 cannot
        $this->assertFalse($owner2->can('update', $boardingHouse));
    }

    public function test_tenant_activation_flow()
    {
        $tenant = User::factory()->create([
            'status' => 'menunggu_aktivasi',
            'password' => null,
            'role' => 'penyewa'
        ]);

        $tokenStr = 'randomtoken123';
        UserActivationToken::create([
            'user_id' => $tenant->id,
            'token_hash' => hash('sha256', $tokenStr),
            'purpose' => 'tenant_activation',
            'expires_at' => now()->addDays(7)
        ]);

        $response = $this->post('/activation', [
            'token' => $tokenStr,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertRedirect('/login');
        
        $tenant->refresh();
        $this->assertEquals('aktif', $tenant->status);
        $this->assertNotNull($tenant->password);
    }
}
