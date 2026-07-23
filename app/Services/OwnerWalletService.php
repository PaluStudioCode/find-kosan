<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\OwnerWallet;
use App\Models\OwnerWalletTransaction;
use Illuminate\Support\Facades\DB;

class OwnerWalletService
{
    /**
     * Credits an owner's wallet once for every paid invoice.
     */
    public function creditPaidInvoice(Invoice $invoice): void
    {
        if ($invoice->status !== 'lunas') {
            return;
        }

        DB::transaction(function () use ($invoice) {
            $wallet = OwnerWallet::firstOrCreate(
                ['owner_id' => $invoice->owner_id],
                ['available_balance' => 0, 'pending_withdrawal_balance' => 0]
            );

            $transaction = OwnerWalletTransaction::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'owner_wallet_id' => $wallet->id,
                    'type' => 'payment_credit',
                    'amount' => $invoice->amount,
                    'description' => "Dana sewa dari tagihan #{$invoice->id}",
                ]
            );

            if ($transaction->wasRecentlyCreated) {
                $wallet->increment('available_balance', $invoice->amount);
            }
        });
    }
}
