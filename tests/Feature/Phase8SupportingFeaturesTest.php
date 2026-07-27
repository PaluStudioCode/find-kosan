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
        $user = User::factory()->create(['role' => 'user']);
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

        $response = $this->actingAs($admin)->put("/superadmin/reports/{$report->id}", [
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

        $response = $this->actingAs($admin)->post('/superadmin/users', [
            'name' => 'New Tenant',
            'email' => 'newtenant@example.com',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'aktif',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'newtenant@example.com',
        ]);

        $user = User::where('email', 'newtenant@example.com')->first();

        // Update user
        $response = $this->actingAs($admin)->put("/superadmin/users/{$user->id}", [
            'name' => 'Updated Tenant',
            'email' => 'updatedtenant@example.com',
            'role' => 'user',
            'status' => 'nonaktif',
        ]);

        $this->assertEquals('nonaktif', $user->refresh()->status);

        // Delete user
        $response = $this->actingAs($admin)->delete("/superadmin/users/{$user->id}");
        $this->assertSoftDeleted($user);
    }

    public function test_super_admin_cannot_delete_user_with_active_relations()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        BoardingHouse::factory()->create(['admin_id' => $admin->id]);

        $response = $this->actingAs($superAdmin)->delete("/superadmin/users/{$admin->id}");
        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted($admin);
    }

    public function test_payment_approval_creates_activity_log()
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
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'payment.approved',
        ]);
    }
}
