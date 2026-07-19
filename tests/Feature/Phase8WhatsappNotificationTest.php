<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Tenancy;
use App\Models\User;
use App\Models\BoardingHouse;
use App\Models\Room;
use App\Models\WhatsappNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Phase8WhatsappNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_notification_when_booking()
    {
        $owner = User::factory()->create(['role' => 'pemilik_kos', 'whatsapp_number' => '08123456789']);
        $tenant = User::factory()->create(['role' => 'penyewa']);
        
        $kos = BoardingHouse::factory()->create(['owner_id' => $owner->id]);
        $room = Room::factory()->create(['boarding_house_id' => $kos->id, 'capacity' => 2, 'price_period' => 'bulanan']);

        $response = $this->actingAs($tenant)->post("/tenant/rooms/{$room->id}/book", [
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'occupant_count' => 1,
        ]);

        $this->assertDatabaseHas('whatsapp_notifications', [
            'tenant_id' => $owner->id,
            'message_type' => 'pembayaran_baru'
        ]);
    }

    public function test_it_generates_reminders_for_due_invoices()
    {
        $tenant = User::factory()->create(['role' => 'penyewa', 'whatsapp_number' => '08987654321']);
        $tenancy = Tenancy::factory()->create(['tenant_id' => $tenant->id]);
        $invoice = Invoice::factory()->create([
            'tenancy_id' => $tenancy->id,
            'tenant_id' => $tenant->id,
            'status' => 'belum_dibayar',
            'due_date' => now()->addDays(2)
        ]);

        Artisan::call('whatsapp:reminders');

        $this->assertDatabaseHas('whatsapp_notifications', [
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'message_type' => 'pengingat_jatuh_tempo'
        ]);
    }

    public function test_process_command_dispatches_jobs_and_updates_status()
    {
        $tenant = User::factory()->create(['role' => 'penyewa', 'whatsapp_number' => '08987654321']);
        $tenancy = Tenancy::factory()->create(['tenant_id' => $tenant->id]);
        $invoice = Invoice::factory()->create([
            'tenancy_id' => $tenancy->id,
            'tenant_id' => $tenant->id,
        ]);

        $notif = WhatsappNotification::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'phone_number' => '08987654321',
            'message_type' => 'pembayaran_baru',
            'message_body' => 'Test message',
            'scheduled_date' => today(),
            'status' => 'belum_dikirim',
        ]);

        Artisan::call('whatsapp:process');

        // Since queue is sync in testing (or we can just check if status is updated if we let the job run)
        // By default testing uses Sync queue, so the Job will run immediately.
        $this->assertEquals('terkirim', $notif->refresh()->status);
    }
}
