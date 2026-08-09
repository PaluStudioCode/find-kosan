<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AiDataController;
use App\Models\BoardingHouse;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use App\Models\WhatsappNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AiTenantReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_kos_detail_does_not_contain_the_owner_whatsapp_number(): void
    {
        $kos = BoardingHouse::factory()->create([
            'public_contact_whatsapp_number' => '628111222333',
        ]);

        $response = app(AiDataController::class)->kosDetail($kos->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('public_contact_whatsapp_number', $response->getData(true));
    }

    public function test_active_tenant_report_is_sent_to_the_owner_account_whatsapp_number(): void
    {
        [$tenant, $owner, $tenancy] = $this->createActiveTenancy();
        $verificationToken = $this->verifyTenantSession($tenant);

        Http::fake([
            'http://127.0.0.1:3001/api/sessions/0/send' => Http::response(['success' => true]),
        ]);

        $response = app(AiDataController::class)->submitTenantReport(
            Request::create('/api/ai/user/'.$tenant->whatsapp_number.'/report', 'POST', [
                'tenancy_id' => $tenancy->id,
                'report' => 'AC kamar tidak dingin sejak pagi.',
            ], [], [], [
                'HTTP_X_AI_REQUESTER_PHONE' => $tenant->whatsapp_number,
                'HTTP_X_AI_VERIFICATION_TOKEN' => $verificationToken,
            ]),
            $tenant->whatsapp_number,
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Laporan Anda telah diteruskan ke pemilik kos.',
        ], $response->getData(true));
        $this->assertTrue(Route::has('ai.user.report'));

        $notification = WhatsappNotification::query()->sole();
        $this->assertSame($tenant->id, $notification->user_id);
        $this->assertSame($owner->id, $notification->admin_id);
        $this->assertSame($owner->whatsapp_number, $notification->phone_number);
        $this->assertSame('laporan_penyewa', $notification->message_type);
        $this->assertSame('terkirim', $notification->status);

        Http::assertSent(function ($request) use ($owner, $tenancy) {
            $payload = $request->data();

            return $payload['phone'] === $owner->whatsapp_number
                && str_contains($payload['message'], $tenancy->boardingHouse->name)
                && str_contains($payload['message'], 'AC kamar tidak dingin sejak pagi.');
        });
    }

    public function test_inactive_tenant_cannot_send_a_report(): void
    {
        [$tenant, , $tenancy] = $this->createActiveTenancy();
        $verificationToken = $this->verifyTenantSession($tenant);
        $tenancy->update(['status' => 'selesai']);

        Http::fake();

        $response = app(AiDataController::class)->submitTenantReport(
            Request::create('/api/ai/user/'.$tenant->whatsapp_number.'/report', 'POST', [
                'tenancy_id' => $tenancy->id,
                'report' => 'Lampu kamar mati.',
            ], [], [], [
                'HTTP_X_AI_REQUESTER_PHONE' => $tenant->whatsapp_number,
                'HTTP_X_AI_VERIFICATION_TOKEN' => $verificationToken,
            ]),
            $tenant->whatsapp_number,
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Sewa aktif untuk laporan ini tidak ditemukan.',
        ], $response->getData(true));
        Http::assertNothingSent();
    }

    public function test_owner_whatsapp_is_only_returned_for_the_same_active_tenant(): void
    {
        [$tenant, $owner] = $this->createActiveTenancy();
        $verificationToken = $this->verifyTenantSession($tenant);

        $authorizedResponse = app(AiDataController::class)->userTenancy(
            Request::create('/api/ai/user/'.$tenant->whatsapp_number.'/tenancy', 'GET', [], [], [], [
                'HTTP_X_AI_REQUESTER_PHONE' => $tenant->whatsapp_number,
                'HTTP_X_AI_VERIFICATION_TOKEN' => $verificationToken,
            ]),
            $tenant->whatsapp_number,
        );
        $unauthorizedResponse = app(AiDataController::class)->userTenancy(
            Request::create('/api/ai/user/'.$tenant->whatsapp_number.'/tenancy', 'GET', [], [], [], [
                'HTTP_X_AI_REQUESTER_PHONE' => '6280000000000',
            ]),
            $tenant->whatsapp_number,
        );

        $this->assertSame($owner->whatsapp_number, $authorizedResponse->getData(true)['tenancies'][0]['kos_contact_wa']);
        $this->assertSame(403, $unauthorizedResponse->getStatusCode());
    }

    private function createActiveTenancy(): array
    {
        $tenant = User::factory()->create([
            'role' => 'user',
            'whatsapp_number' => '6281234567890',
        ]);
        $owner = User::factory()->create([
            'role' => 'admin',
            'whatsapp_number' => '6289876543210',
        ]);
        $kos = BoardingHouse::factory()->create([
            'admin_id' => $owner->id,
            'name' => 'Kos Mawar',
        ]);
        $room = Room::factory()->create([
            'boarding_house_id' => $kos->id,
            'name' => 'Kamar A',
            'room_number' => 'A1',
            'status' => 'terisi',
        ]);
        $tenancy = Tenancy::factory()->create([
            'user_id' => $tenant->id,
            'admin_id' => $owner->id,
            'boarding_house_id' => $kos->id,
            'room_id' => $room->id,
            'status' => 'aktif',
        ]);

        return [$tenant, $owner, $tenancy];
    }

    private function verifyTenantSession(User $tenant): string
    {
        $token = 'verified-session-token';
        Cache::put('ai_verified_session_'.$tenant->whatsapp_number, $token, now()->addMinutes(30));

        return $token;
    }
}
