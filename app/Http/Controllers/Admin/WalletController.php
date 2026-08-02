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
            'filters' => $request->only(['type', 'month', 'year']),
            'transactions' => Inertia::defer(function () use ($wallet, $request) {
                return AdminWalletTransaction::with('invoice')
                    ->where('admin_wallet_id', $wallet->id)
                    ->whereNotIn('type', ['withdrawal_hold', 'withdrawal_release'])
                    ->when($request->type, function ($query, $type) {
                        if ($type === 'pemasukan') {
                            $query->whereIn('type', ['payment_credit']);
                        } elseif ($type === 'pengeluaran') {
                            $query->whereNotIn('type', ['payment_credit']);
                        }
                    })
                    ->when($request->month, function ($query, $month) {
                        $query->whereMonth('created_at', $month);
                    })
                    ->when($request->year, function ($query, $year) {
                        $query->whereYear('created_at', $year);
                    })
                    ->latest()
                    ->paginate(5)
                    ->withQueryString();
            }),
            'withdrawals' => Inertia::defer(function () use ($adminId) {
                return WithdrawalRequest::where('admin_id', $adminId)
                    ->latest()
                    ->paginate(5, ['*'], 'withdrawals_page')
                    ->withQueryString();
            }),
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

    public function export(Request $request)
    {
        $adminId = $request->user()->id;
        $wallet = AdminWallet::where('admin_id', $adminId)->first();

        if (!$wallet) {
            return back()->with('error', 'Dompet tidak ditemukan.');
        }

        $transactions = AdminWalletTransaction::with('invoice')
            ->where('admin_wallet_id', $wallet->id)
            ->whereNotIn('type', ['withdrawal_hold', 'withdrawal_release'])
            ->when($request->type, function ($query, $type) {
                if ($type === 'pemasukan') {
                    $query->whereIn('type', ['payment_credit']);
                } elseif ($type === 'pengeluaran') {
                    $query->whereNotIn('type', ['payment_credit']);
                }
            })
            ->when($request->month, function ($query, $month) {
                $query->whereMonth('created_at', $month);
            })
            ->when($request->year, function ($query, $year) {
                $query->whereYear('created_at', $year);
            })
            ->oldest()
            ->get();

        $fileName = 'Laporan_Mutasi_Kos_' . date('Ymd_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\WalletTransactionsExport($transactions), 
            $fileName
        );
    }
}
