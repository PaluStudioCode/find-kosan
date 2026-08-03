<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminWallet;
use App\Models\AdminWalletTransaction;
use App\Models\User;
use App\Models\WhatsappNotification;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/Withdrawals/Index', [
            'metrics' => Inertia::defer(function () {
                return [
                    'pendingAmount' => (float) WithdrawalRequest::where('status', 'menunggu_persetujuan')->sum('amount'),
                    'completedPayouts' => (float) WithdrawalRequest::where('status', 'selesai')->sum('net_amount'),
                    'collectedPph' => (float) WithdrawalRequest::where('status', 'selesai')->sum('pph_amount'),
                ];
            }),
            'withdrawals' => Inertia::defer(fn () => WithdrawalRequest::with(['admin', 'reviewer', 'transferer'])
                ->latest()
                ->paginate(10)
                ->withQueryString()),
        ]);
    }

    public function show(WithdrawalRequest $withdrawal)
    {
        $withdrawal->load(['admin', 'reviewer', 'transferer']);

        return Inertia::render('SuperAdmin/Withdrawals/Show', compact('withdrawal'));
    }

    public function approve(Request $request, WithdrawalRequest $withdrawal)
    {
        $validated = $request->validate([
            'transfer_reference' => 'required|string|max:100',
            'transfer_proof' => 'required|image|max:2048',
        ]);

        $proofPath = '/storage/'.$request->file('transfer_proof')->store('withdrawal-proofs', 'public');

        DB::transaction(function () use ($request, $withdrawal, $validated, $proofPath) {
            $withdrawal = WithdrawalRequest::lockForUpdate()->findOrFail($withdrawal->id);
            if ($withdrawal->status !== 'menunggu_persetujuan') {
                abort(422, 'Permintaan penarikan ini sudah diproses.');
            }

            $wallet = AdminWallet::where('admin_id', $withdrawal->admin_id)->lockForUpdate()->firstOrFail();
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

            AdminWalletTransaction::create([
                'admin_wallet_id' => $wallet->id,
                'withdrawal_request_id' => $withdrawal->id,
                'type' => 'withdrawal_debit',
                'amount' => $withdrawal->amount,
                'description' => "Penarikan #{$withdrawal->id} berhasil ditransfer",
            ]);

            $this->log($request, 'withdrawal.completed', $withdrawal);

            // WhatsApp notification to owner
            $admin = $withdrawal->admin ?? User::find($withdrawal->admin_id);
            if ($admin && $admin->whatsapp_number) {
                WhatsappNotification::create([
                    'user_id' => $admin->id,
                    'admin_id' => $withdrawal->admin_id,
                    'send_via' => 'admin',
                    'phone_number' => $admin->whatsapp_number,
                    'message_type' => 'penarikan_disetujui',
                    'message_body' => "Halo {$admin->name}, penarikan dana sebesar Rp".number_format($withdrawal->net_amount ?: $withdrawal->amount, 0, ',', '.')." (setelah PPh) telah disetujui dan ditransfer ke rekening Anda. No. Ref: {$validated['transfer_reference']}",
                    'scheduled_date' => today(),
                    'status' => 'belum_dikirim',
                ]);
            }
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

            $wallet = AdminWallet::where('admin_id', $withdrawal->admin_id)->lockForUpdate()->firstOrFail();
            $wallet->increment('available_balance', $withdrawal->amount);
            $wallet->decrement('pending_withdrawal_balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'ditolak',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'],
            ]);

            AdminWalletTransaction::create([
                'admin_wallet_id' => $wallet->id,
                'withdrawal_request_id' => $withdrawal->id,
                'type' => 'withdrawal_release',
                'amount' => $withdrawal->amount,
                'description' => "Dana penarikan #{$withdrawal->id} dikembalikan",
            ]);

            $this->log($request, 'withdrawal.rejected', $withdrawal);

            // WhatsApp notification to owner
            $admin = $withdrawal->admin ?? User::find($withdrawal->admin_id);
            if ($admin && $admin->whatsapp_number) {
                WhatsappNotification::create([
                    'user_id' => $admin->id,
                    'admin_id' => $withdrawal->admin_id,
                    'send_via' => 'admin',
                    'phone_number' => $admin->whatsapp_number,
                    'message_type' => 'penarikan_ditolak',
                    'message_body' => "Halo {$admin->name}, penarikan dana sebesar Rp".number_format($withdrawal->amount, 0, ',', '.')." ditolak. Alasan: {$validated['review_note']}. Saldo Anda telah dikembalikan.",
                    'scheduled_date' => today(),
                    'status' => 'belum_dikirim',
                ]);
            }
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
            'metadata' => ['amount' => $withdrawal->amount, 'admin_id' => $withdrawal->admin_id],
        ]);
    }
}
