<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminWallet;
use App\Models\AdminWalletTransaction;
use App\Models\Setting;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $adminId = $request->user()->id;
        $wallet = AdminWallet::firstOrCreate(
            ['admin_id' => $adminId],
            ['available_balance' => 0, 'pending_withdrawal_balance' => 0]
        );

        $minWithdrawal = (float) (Setting::getSetting('min_withdrawal') ?: 50000);

        return Inertia::render('Admin/Wallet/Index', [
            'wallet' => $wallet,
            'min_withdrawal' => $minWithdrawal,
            'transactions' => AdminWalletTransaction::with('invoice')
                ->where('admin_wallet_id', $wallet->id)
                ->whereNotIn('type', ['withdrawal_hold', 'withdrawal_release'])
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'withdrawals' => WithdrawalRequest::where('admin_id', $adminId)
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    public function storeWithdrawal(Request $request)
    {
        $minWithdrawal = (float) (Setting::getSetting('min_withdrawal') ?: 50000);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.$minWithdrawal],
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:150',
            'owner_note' => 'nullable|string|max:1000',
        ], [
            'amount.min' => 'Minimal penarikan adalah Rp '.number_format($minWithdrawal, 0, ',', '.'),
        ]);

        DB::transaction(function () use ($request, $validated) {
            $wallet = AdminWallet::where('admin_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['admin_id' => $request->user()->id],
                    ['available_balance' => 0, 'pending_withdrawal_balance' => 0]
                );

            if ((float) $validated['amount'] > (float) $wallet->available_balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo tersedia tidak mencukupi untuk penarikan ini.',
                ]);
            }

            $withdrawal = WithdrawalRequest::create([
                ...$validated,
                'admin_id' => $request->user()->id,
                'status' => 'menunggu_persetujuan',
            ]);

            $wallet->decrement('available_balance', $validated['amount']);
            $wallet->increment('pending_withdrawal_balance', $validated['amount']);

            AdminWalletTransaction::create([
                'admin_wallet_id' => $wallet->id,
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
