<?php

namespace Tests\Feature;

use App\Models\BoardingHouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseAndModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_run_successfully_and_tables_exist()
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('boarding_houses'));
        $this->assertTrue(Schema::hasTable('rooms'));
        $this->assertTrue(Schema::hasTable('facilities'));
        $this->assertTrue(Schema::hasTable('tenancies'));
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasTable('payments'));
        $this->assertTrue(Schema::hasTable('whatsapp_notifications'));
    }

    public function test_seeder_creates_super_admin_and_facilities()
    {
        $this->artisan('db:seed')->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@kosonline.test',
            'role' => 'super_admin',
        ]);

        $this->assertDatabaseHas('facilities', [
            'name' => 'WiFi / Internet',
            'type' => 'kos',
        ]);

        $this->assertDatabaseHas('facilities', [
            'name' => 'Kasur & Bantal',
            'type' => 'kamar',
        ]);
    }

    public function test_user_boarding_house_relationship()
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $boardingHouse = BoardingHouse::create([
            'admin_id' => $user->id,
            'name' => 'Kos Test',
            'description' => 'Desc',
            'address' => 'Addr',
            'status' => 'draft',
        ]);

        $this->assertEquals(1, $user->boardingHouses()->count());
        $this->assertEquals($user->id, $boardingHouse->admin->id);
    }
}
