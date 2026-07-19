<?php

namespace Database\Factories;

use App\Models\Tenancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenancy>
 */
class TenancyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\User::factory()->create(['role' => 'penyewa'])->id,
            'owner_id' => \App\Models\User::factory()->create(['role' => 'pemilik_kos'])->id,
            'boarding_house_id' => \App\Models\BoardingHouse::factory(),
            'room_id' => \App\Models\Room::factory(),
            'start_date' => now(),
            'status' => 'aktif',
        ];
    }
}
