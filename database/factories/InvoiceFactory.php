<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenancy_id' => \App\Models\Tenancy::factory(),
            'tenant_id' => \App\Models\User::factory()->create(['role' => 'penyewa'])->id,
            'owner_id' => \App\Models\User::factory()->create(['role' => 'pemilik_kos'])->id,
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 1000000,
            'due_date' => now()->addDays(7),
            'status' => 'belum_dibayar',
        ];
    }
}
