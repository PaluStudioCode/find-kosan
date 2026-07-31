<?php

namespace Database\Factories;

use App\Models\BoardingHouse;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
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
            'user_id' => User::factory()->create(['role' => 'user'])->id,
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'boarding_house_id' => BoardingHouse::factory(),
            'room_id' => Room::factory(),
            'start_date' => now(),
            'status' => 'aktif',
        ];
    }
}
