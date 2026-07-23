<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OwnerWallet;
use App\Models\OwnerWalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $request->user()->id;
        $wallet = OwnerWallet::firstOrCreate(
            ['owner_id' => $ownerId],
            ['available_balance' => 0, 'pending_withdrawal_balance' => 0]
        );

        return Inertia::render('Owner/Wallet/Index', [
            'wallet' => $wallet,
            'transactions' => OwnerWalletTransaction::with('invoice')
                ->where('owner_wallet_id', $wallet->id)
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'withdrawals' => WithdrawalRequest::where('owner_id', $ownerId)
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    public function storeWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:150',
            'owner_note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $wallet = OwnerWallet::where('owner_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['owner_id' => $request->user()->id],
                    ['available_balance' => 0, 'pending_withdrawal_balance' => 0]
                );

            if ((float) $validated['amount'] > (float) $wallet->available_balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo tersedia tidak mencukupi untuk penarikan ini.',
                ]);
            }

            $withdrawal = WithdrawalRequest::create([
                ...$validated,
                'owner_id' => $request->user()->id,
                'status' => 'menunggu_persetujuan',
            ]);

            $wallet->decrement('available_balance', $validated['amount']);
            $wallet->increment('pending_withdrawal_balance', $validated['amount']);

            OwnerWalletTransaction::create([
                'owner_wallet_id' => $wallet->id,
                'withdrawal_request_id' => $withdrawal->id,
                'type' => 'withdrawal_hold',
                'amount' => $validated['amount'],
                'description' => "Dana ditahan untuk penarikan #{$withdrawal->id}",
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'withdrawal.requested',
                'subject_type' => WithdrawalRequest::class,
                'subject_id' => $withdrawal->id,
                'metadata' => ['amount' => $validated['amount']],
            ]);
        });

        return back()->with('success', 'Permintaan penarikan berhasil dikirim dan menunggu persetujuan admin.');
    }
}
