<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_settings()
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->post(route('superadmin.settings.update'), [
            'app_name' => 'My New App',
            'fee_percent' => '5.5',
            'min_withdrawal' => '100000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('My New App', Setting::getSetting('app_name'));
        $this->assertEquals('5.5', Setting::getSetting('fee_percent'));
        $this->assertEquals('100000', Setting::getSetting('min_withdrawal'));
    }

    public function test_non_admin_cannot_update_settings()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->post(route('superadmin.settings.update'), [
            'app_name' => 'Hacked App',
        ]);

        $response->assertStatus(403); // Role middleware should block
    }
}
