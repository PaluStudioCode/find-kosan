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
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($superAdmin)->get('/dashboard')->assertRedirect('/superadmin/dashboard');
        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/');
    }

    public function test_registration_creates_pemilik_kos()
    {
        $response = $this->post('/register', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'whatsapp_number' => '6281234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => 'admin',
            'status' => 'aktif'
        ]);
    }

    public function test_role_middleware_returns_403()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/superadmin/dashboard')->assertStatus(403);
    }

    public function test_policy_denies_access_to_other_owners_data()
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);

        $boardingHouse = BoardingHouse::create([
            'admin_id' => $admin1->id,
            'name' => 'Kos 1',
            'description' => 'Desc',
            'address' => 'Addr',
            'status' => 'draft'
        ]);

        // owner1 can update
        $this->assertTrue($admin1->can('update', $boardingHouse));
        
        // owner2 cannot
        $this->assertFalse($admin2->can('update', $boardingHouse));
    }

    public function test_tenant_activation_flow()
    {
        $user = User::factory()->create([
            'status' => 'menunggu_aktivasi',
            'password' => null,
            'role' => 'user'
        ]);

        $tokenStr = 'randomtoken123';
        UserActivationToken::create([
            'user_id' => $user->id,
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
        
        $user->refresh();
        $this->assertEquals('aktif', $user->status);
        $this->assertNotNull($user->password);
    }
}
