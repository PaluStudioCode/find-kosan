<?php

namespace Database\Factories;

use App\Models\BoardingHouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardingHouse>
 */
class BoardingHouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => \App\Models\User::factory()->create(['role' => 'pemilik_kos'])->id,
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'public_contact_name' => fake()->name(),
            'public_contact_whatsapp_number' => '08123456789',
            'city' => fake()->city(),
            'district' => fake()->city(),
            'subdistrict' => fake()->city(),
            'status' => 'dipublikasikan',
        ];
    }
}
