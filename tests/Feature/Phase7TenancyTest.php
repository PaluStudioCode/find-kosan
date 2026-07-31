<?php

namespace Tests\Feature;

use App\Models\BoardingHouse;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase7TenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tenant_can_book_room()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'aktif']);

        $kos = BoardingHouse::factory()->create(['admin_id' => $admin->id, 'status' => 'dipublikasikan']);
        $room = Room::factory()->create(['boarding_house_id' => $kos->id, 'capacity' => 2, 'price_period' => 'bulanan']);

        $response = $this->actingAs($user)->post("/user/rooms/{$room->id}/book", [
            'start_date' => now()->addDays(2)->format('Y-m-d'),
            'occupant_count' => 1,
        ]);

        $tenancy = Tenancy::where('user_id', $user->id)->first();
        $this->assertNotNull($tenancy);
        $this->assertEquals('nonaktif', $tenancy->status);

        $response->assertRedirect("/user/tenancies/{$tenancy->id}");

        $this->assertDatabaseHas('invoices', [
            'tenancy_id' => $tenancy->id,
            'status' => 'belum_dibayar',
        ]);
    }

    public function test_tenant_can_upload_payment_proof()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'aktif']);
        $tenancy = Tenancy::factory()->create(['user_id' => $user->id, 'admin_id' => $admin->id, 'status' => 'nonaktif']);
        $invoice = Invoice::factory()->create(['tenancy_id' => $tenancy->id, 'user_id' => $user->id, 'admin_id' => $admin->id, 'status' => 'belum_dibayar']);

        $file = UploadedFile::fake()->image('proof.jpg');

        $response = $this->actingAs($user)->post("/user/invoices/{$invoice->id}/payment", [
            'proof_file' => $file,
        ]);

        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('menunggu_konfirmasi', $invoice->status);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'status' => 'menunggu_konfirmasi',
        ]);
    }

    public function test_owner_can_approve_payment()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'aktif']);

        $kos = BoardingHouse::factory()->create(['admin_id' => $admin->id, 'status' => 'dipublikasikan']);
        $room = Room::factory()->create(['boarding_house_id' => $kos->id, 'capacity' => 1]);

        $tenancy = Tenancy::factory()->create(['user_id' => $user->id, 'admin_id' => $admin->id, 'room_id' => $room->id, 'status' => 'nonaktif']);
        $invoice = Invoice::factory()->create(['tenancy_id' => $tenancy->id, 'user_id' => $user->id, 'admin_id' => $admin->id, 'status' => 'menunggu_konfirmasi']);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'amount' => 1000000,
            'payment_date' => now(),
            'status' => 'menunggu_konfirmasi',
        ]);

        $response = $this->actingAs($admin)->post("/admin/payments/{$payment->id}/confirm", [
            'action' => 'approve',
        ]);

        $response->assertSessionHas('success');

        $this->assertEquals('diterima', $payment->refresh()->status);
        $this->assertEquals('lunas', $invoice->refresh()->status);
        $this->assertEquals('aktif', $tenancy->refresh()->status);
        $this->assertEquals('terisi', $room->refresh()->status);
    }
}
