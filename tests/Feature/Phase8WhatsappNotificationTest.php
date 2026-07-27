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
        $admin = User::factory()->create(['role' => 'admin', 'whatsapp_number' => '08123456789']);
        $user = User::factory()->create(['role' => 'user']);
        
        $kos = BoardingHouse::factory()->create(['admin_id' => $admin->id]);
        $room = Room::factory()->create(['boarding_house_id' => $kos->id, 'capacity' => 2, 'price_period' => 'bulanan']);

        $response = $this->actingAs($user)->post("/user/rooms/{$room->id}/book", [
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'occupant_count' => 1,
        ]);

        $this->assertDatabaseHas('whatsapp_notifications', [
            'user_id' => $admin->id,
            'message_type' => 'pembayaran_baru'
        ]);
    }

    public function test_it_generates_reminders_for_due_invoices()
    {
        $user = User::factory()->create(['role' => 'user', 'whatsapp_number' => '08987654321']);
        $tenancy = Tenancy::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'tenancy_id' => $tenancy->id,
            'user_id' => $user->id,
            'status' => 'belum_dibayar',
            'due_date' => today()
        ]);

        Artisan::call('whatsapp:reminders');

        $this->assertDatabaseHas('whatsapp_notifications', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'message_type' => 'pengingat_jatuh_tempo'
        ]);
    }

    public function test_process_command_dispatches_jobs_and_updates_status()
    {
        $admin = User::factory()->create(['role' => 'admin', 'whatsapp_number' => '08123456789']);
        $user = User::factory()->create(['role' => 'user', 'whatsapp_number' => '08987654321']);
        $tenancy = Tenancy::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'tenancy_id' => $tenancy->id,
            'user_id' => $user->id,
            'admin_id' => $admin->id,
        ]);

        $notif = WhatsappNotification::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'phone_number' => '08987654321',
            'message_type' => 'pembayaran_baru',
            'message_body' => 'Test message',
            'scheduled_date' => today(),
            'status' => 'belum_dikirim',
        ]);

        Artisan::call('whatsapp:process');

        // The job will run synchronously in testing.
        // It will fail because WA service is not running, but the status should change from 'belum_dikirim'.
        $notif->refresh();
        $this->assertContains($notif->status, ['terkirim', 'gagal']);
    }
}
