<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OwnerWallet;
use App\Models\OwnerWalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Withdrawals/Index', [
            'withdrawals' => WithdrawalRequest::with(['owner', 'reviewer', 'transferer'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(WithdrawalRequest $withdrawal)
    {
        $withdrawal->load(['owner', 'reviewer', 'transferer']);

        return Inertia::render('Admin/Withdrawals/Show', compact('withdrawal'));
    }

    public function approve(Request $request, WithdrawalRequest $withdrawal)
    {
        $validated = $request->validate([
            'transfer_reference' => 'required|string|max:100',
            'transfer_proof' => 'required|image|max:2048',
        ]);

        $proofPath = '/storage/' . $request->file('transfer_proof')->store('withdrawal-proofs', 'public');

        DB::transaction(function () use ($request, $withdrawal, $validated, $proofPath) {
            $withdrawal = WithdrawalRequest::lockForUpdate()->findOrFail($withdrawal->id);
            if ($withdrawal->status !== 'menunggu_persetujuan') {
                abort(422, 'Permintaan penarikan ini sudah diproses.');
            }

            $wallet = OwnerWallet::where('owner_id', $withdrawal->owner_id)->lockForUpdate()->firstOrFail();
            $wallet->decrement('pending_withdrawal_balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'selesai',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'transferred_by' => $request->user()->id,
                'transferred_at' => now(),
                'transfer_reference' => $validated['transfer_reference'],
                'transfer_proof_path' => $proofPath,
            ]);

            OwnerWalletTransaction::create([
                'owner_wallet_id' => $wallet->id,
                'withdrawal_request_id' => $withdrawal->id,
                'type' => 'withdrawal_debit',
                'amount' => $withdrawal->amount,
                'description' => "Penarikan #{$withdrawal->id} berhasil ditransfer",
            ]);

            $this->log($request, 'withdrawal.completed', $withdrawal);
        });

        return back()->with('success', 'Penarikan telah disetujui dan ditransfer.');
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal)
    {
        $validated = $request->validate(['review_note' => 'required|string|max:1000']);

        DB::transaction(function () use ($request, $withdrawal, $validated) {
            $withdrawal = WithdrawalRequest::lockForUpdate()->findOrFail($withdrawal->id);
            if ($withdrawal->status !== 'menunggu_persetujuan') {
                abort(422, 'Hanya penarikan yang menunggu persetujuan yang dapat ditolak.');
            }

            $wallet = OwnerWallet::where('owner_id', $withdrawal->owner_id)->lockForUpdate()->firstOrFail();
            $wallet->increment('available_balance', $withdrawal->amount);
            $wallet->decrement('pending_withdrawal_balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'ditolak',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'],
            ]);

            OwnerWalletTransaction::create([
                'owner_wallet_id' => $wallet->id,
                'withdrawal_request_id' => $withdrawal->id,
                'type' => 'withdrawal_release',
                'amount' => $withdrawal->amount,
                'description' => "Dana penarikan #{$withdrawal->id} dikembalikan",
            ]);

            $this->log($request, 'withdrawal.rejected', $withdrawal);
        });

        return back()->with('success', 'Penarikan ditolak dan saldo pemilik dikembalikan.');
    }

    private function log(Request $request, string $action, WithdrawalRequest $withdrawal): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => WithdrawalRequest::class,
            'subject_id' => $withdrawal->id,
            'metadata' => ['amount' => $withdrawal->amount, 'owner_id' => $withdrawal->owner_id],
        ]);
    }
}
