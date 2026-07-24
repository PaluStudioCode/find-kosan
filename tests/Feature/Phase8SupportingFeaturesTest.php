<?php

namespace Tests\Feature;

use App\Models\BoardingHouse;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8SupportingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_user_can_submit_report()
    {
        $user = User::factory()->create(['role' => 'penyewa']);
        $kos = BoardingHouse::factory()->create();

        $response = $this->actingAs($user)->post('/reports', [
            'boarding_house_id' => $kos->id,
            'category' => 'data_kos_tidak_valid',
            'description' => 'Alamat tidak sesuai dengan yang di peta.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reports', [
            'reporter_id' => $user->id,
            'boarding_house_id' => $kos->id,
            'category' => 'data_kos_tidak_valid',
        ]);
    }

    public function test_super_admin_can_resolve_report_and_creates_activity_log()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $user->id,
            'category' => 'lainnya',
            'description' => 'Test report',
            'status' => 'menunggu',
        ]);

        $response = $this->actingAs($admin)->put("/admin/reports/{$report->id}", [
            'status' => 'selesai',
            'resolution_note' => 'Sudah diperbaiki',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('selesai', $report->refresh()->status);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'report.resolved',
        ]);
    }

    public function test_super_admin_can_manage_users()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Tenant',
            'email' => 'newtenant@example.com',
            'password' => 'password123',
            'role' => 'penyewa',
            'status' => 'aktif',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'newtenant@example.com',
        ]);

        $user = User::where('email', 'newtenant@example.com')->first();

        // Update user
        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'Updated Tenant',
            'email' => 'updatedtenant@example.com',
            'role' => 'penyewa',
            'status' => 'nonaktif',
        ]);

        $this->assertEquals('nonaktif', $user->refresh()->status);

        // Delete user
        $response = $this->actingAs($admin)->delete("/admin/users/{$user->id}");
        $this->assertSoftDeleted($user);
    }

    public function test_super_admin_cannot_delete_user_with_active_relations()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'pemilik_kos']);
        BoardingHouse::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($admin)->delete("/admin/users/{$owner->id}");
        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted($owner);
    }

    public function test_payment_approval_creates_activity_log()
    {
        $owner = User::factory()->create(['role' => 'pemilik_kos', 'status' => 'aktif']);
        $tenant = User::factory()->create(['role' => 'penyewa', 'status' => 'aktif']);

        $kos = BoardingHouse::factory()->create(['owner_id' => $owner->id, 'status' => 'dipublikasikan']);
        $room = Room::factory()->create(['boarding_house_id' => $kos->id, 'capacity' => 1]);

        $tenancy = Tenancy::factory()->create(['tenant_id' => $tenant->id, 'owner_id' => $owner->id, 'room_id' => $room->id, 'status' => 'nonaktif']);
        $invoice = Invoice::factory()->create(['tenancy_id' => $tenancy->id, 'tenant_id' => $tenant->id, 'owner_id' => $owner->id, 'status' => 'menunggu_konfirmasi']);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'owner_id' => $owner->id,
            'amount' => 1000000,
            'payment_date' => now(),
            'status' => 'menunggu_konfirmasi',
        ]);

        $response = $this->actingAs($owner)->post("/owner/payments/{$payment->id}/confirm", [
            'action' => 'approve',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $owner->id,
            'action' => 'payment.approved',
        ]);
    }
}
