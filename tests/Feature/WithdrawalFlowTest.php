<?php

namespace Tests\Feature;

use App\Models\AdminWallet;
use App\Models\Invoice;
use App\Models\Tenancy;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\AdminWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_invoice_is_credited_only_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $tenancy = Tenancy::factory()->create(['admin_id' => $admin->id, 'user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'tenancy_id' => $tenancy->id,
            'amount' => 250000,
            'status' => 'lunas',
        ]);

        app(AdminWalletService::class)->creditPaidInvoice($invoice);
        app(AdminWalletService::class)->creditPaidInvoice($invoice);

        $this->assertDatabaseHas('admin_wallets', [
            'admin_id' => $admin->id,
            'available_balance' => 250000,
        ]);
        $this->assertDatabaseCount('admin_wallet_transactions', 1);
    }

    public function test_owner_can_request_and_admin_can_complete_manual_withdrawal(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        AdminWallet::create([
            'admin_id' => $admin->id,
            'available_balance' => 500000,
            'pending_withdrawal_balance' => 0,
        ]);

        $this->actingAs($admin)->post(route('admin.wallet.withdrawals.store'), [
            'amount' => 200000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => $admin->name,
        ])->assertSessionHas('success');

        $withdrawal = WithdrawalRequest::firstOrFail();
        $this->assertSame('menunggu_persetujuan', $withdrawal->status);
        $this->assertSame('300000.00', AdminWallet::firstOrFail()->available_balance);
        $this->assertSame('200000.00', AdminWallet::firstOrFail()->pending_withdrawal_balance);

        $this->actingAs($superAdmin)->post(route('superadmin.withdrawals.approve', $withdrawal), [
            'transfer_reference' => 'TRF-20260723-001',
            'transfer_proof' => UploadedFile::fake()->image('transfer.jpg'),
        ])->assertSessionHas('success');

        $this->assertSame('selesai', $withdrawal->refresh()->status);
        $this->assertSame('0.00', AdminWallet::firstOrFail()->pending_withdrawal_balance);
        $this->assertNotNull($withdrawal->transfer_proof_path);
    }
}
