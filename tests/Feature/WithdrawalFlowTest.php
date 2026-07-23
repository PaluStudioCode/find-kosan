<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\OwnerWallet;
use App\Models\Tenancy;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\OwnerWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_invoice_is_credited_only_once(): void
    {
        $owner = User::factory()->create(['role' => 'pemilik_kos']);
        $tenant = User::factory()->create(['role' => 'penyewa']);
        $tenancy = Tenancy::factory()->create(['owner_id' => $owner->id, 'tenant_id' => $tenant->id]);
        $invoice = Invoice::factory()->create([
            'owner_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'tenancy_id' => $tenancy->id,
            'amount' => 250000,
            'status' => 'lunas',
        ]);

        app(OwnerWalletService::class)->creditPaidInvoice($invoice);
        app(OwnerWalletService::class)->creditPaidInvoice($invoice);

        $this->assertDatabaseHas('owner_wallets', [
            'owner_id' => $owner->id,
            'available_balance' => 250000,
        ]);
        $this->assertDatabaseCount('owner_wallet_transactions', 1);
    }

    public function test_owner_can_request_and_admin_can_complete_manual_withdrawal(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'pemilik_kos']);
        $admin = User::factory()->create(['role' => 'super_admin']);
        OwnerWallet::create([
            'owner_id' => $owner->id,
            'available_balance' => 500000,
            'pending_withdrawal_balance' => 0,
        ]);

        $this->actingAs($owner)->post(route('owner.wallet.withdrawals.store'), [
            'amount' => 200000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => $owner->name,
        ])->assertSessionHas('success');

        $withdrawal = WithdrawalRequest::firstOrFail();
        $this->assertSame('menunggu_persetujuan', $withdrawal->status);
        $this->assertSame('300000.00', OwnerWallet::firstOrFail()->available_balance);
        $this->assertSame('200000.00', OwnerWallet::firstOrFail()->pending_withdrawal_balance);

        $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal), [])
            ->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.withdrawals.complete', $withdrawal), [
            'transfer_reference' => 'TRF-20260723-001',
            'transfer_proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])->assertSessionHas('success');

        $this->assertSame('selesai', $withdrawal->refresh()->status);
        $this->assertSame('0.00', OwnerWallet::firstOrFail()->pending_withdrawal_balance);
        $this->assertNotNull($withdrawal->transfer_proof_path);
    }
}
