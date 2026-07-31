<?php

namespace App\Services;

use App\Models\AdminWallet;
use App\Models\AdminWalletTransaction;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AdminWalletService
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
            $wallet = AdminWallet::firstOrCreate(
                ['admin_id' => $invoice->admin_id],
                ['available_balance' => 0, 'pending_withdrawal_balance' => 0]
            );

            // Calculate Platform Fee
            $feePercent = (float) (Setting::getSetting('fee_percent') ?: 0);
            $feeAmount = $invoice->amount * ($feePercent / 100);
            $creditAmount = $invoice->amount - $feeAmount;

            $description = "Dana sewa dari tagihan #{$invoice->id}";
            if ($feePercent > 0) {
                $description .= " (Dipotong biaya admin {$feePercent}%)";
            }

            $transaction = AdminWalletTransaction::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'admin_wallet_id' => $wallet->id,
                    'type' => 'payment_credit',
                    'amount' => $creditAmount,
                    'description' => $description,
                ]
            );

            if ($transaction->wasRecentlyCreated) {
                $wallet->increment('available_balance', $creditAmount);
            }
        });
    }
}
