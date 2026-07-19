<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'boarding_house_id' => \App\Models\BoardingHouse::factory(),
            'name' => 'Kamar ' . fake()->word(),
            'room_number' => fake()->randomNumber(2),
            'price' => 1000000,
            'price_period' => 'bulanan',
            'capacity' => 1,
            'status' => 'tersedia',
        ];
    }
}
