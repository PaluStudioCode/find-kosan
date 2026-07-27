<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->where('status', 'lunas')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->each(function (object $invoice) {
                $wallet = DB::table('admin_wallets')->where('admin_id', $invoice->admin_id)->first();

                if (!$wallet) {
                    $walletId = DB::table('admin_wallets')->insertGetId([
                        'admin_id' => $invoice->admin_id,
                        'available_balance' => 0,
                        'pending_withdrawal_balance' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $walletId = $wallet->id;
                }

                $exists = DB::table('admin_wallet_transactions')->where('invoice_id', $invoice->id)->exists();
                if (!$exists) {
                    DB::table('admin_wallet_transactions')->insert([
                        'admin_wallet_id' => $walletId,
                        'invoice_id' => $invoice->id,
                        'type' => 'payment_credit',
                        'amount' => $invoice->amount,
                        'description' => "Dana sewa dari tagihan #{$invoice->id}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('admin_wallets')->where('id', $walletId)->increment('available_balance', $invoice->amount);
                }
            });
    }

    public function down(): void
    {
        DB::table('admin_wallet_transactions')->where('type', 'payment_credit')->delete();
        DB::table('admin_wallets')->update(['available_balance' => 0]);
    }
};
