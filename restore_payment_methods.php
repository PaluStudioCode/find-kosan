<?php

// Fix Admin\TenancyController
$adminControllerPath = __DIR__ . '/app/Http/Controllers/Admin/TenancyController.php';
$content = file_get_contents($adminControllerPath);
$confirmMethod = <<<PHP

    public function confirmPayment(\Illuminate\Http\Request \$request, \App\Models\Payment \$payment)
    {
        if (\$payment->admin_id !== auth()->id()) abort(403);
        
        \$request->validate([
            'action' => 'required|in:approve,reject',
            'review_note' => 'nullable|string'
        ]);

        if (\$payment->status !== 'menunggu_konfirmasi') {
            return back()->with('error', 'Pembayaran ini sudah selesai diproses sebelumnya.');
        }

        \$invoice = \$payment->invoice;
        \$tenancy = \$invoice->tenancy;

        if (\$request->action === 'approve') {
            \$payment->update([
                'status' => 'diterima',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_note' => \$request->review_note,
            ]);
            \$invoice->update(['status' => 'lunas']);
            app(\App\Services\AdminWalletService::class)->creditPaidInvoice(\$invoice);
            
            if (\$tenancy->status === 'nonaktif') {
                \$tenancy->update(['status' => 'aktif']);
            }
            
            \$room = \$tenancy->room;
            \$activeTenants = \$room->tenancies()->where('status', 'aktif')->sum('occupant_count');
            if (\$activeTenants >= \$room->capacity) {
                \$room->update(['status' => 'terisi']);
            } else {
                \$room->update(['status' => 'tersedia']);
            }

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'payment.approved',
                'description' => "Menyetujui pembayaran untuk tagihan #{\$invoice->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return back()->with('success', 'Pembayaran disetujui.');
        } else {
            \$payment->update([
                'status' => 'ditolak',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_note' => \$request->review_note,
            ]);
            \$invoice->update(['status' => 'belum_dibayar']);
            
            return back()->with('success', 'Pembayaran ditolak.');
        }
    }
}
PHP;

if (strpos($content, 'function confirmPayment') === false) {
    $content = preg_replace('/\}\s*$/', $confirmMethod, $content);
    file_put_contents($adminControllerPath, $content);
}

// Fix User\TenancyController
$userControllerPath = __DIR__ . '/app/Http/Controllers/User/TenancyController.php';
$content = file_get_contents($userControllerPath);
$uploadMethod = <<<PHP

    public function uploadPayment(\Illuminate\Http\Request \$request, \App\Models\Invoice \$invoice)
    {
        if (\$invoice->user_id !== auth()->id()) abort(403);
        
        \$request->validate([
            'proof_file' => 'required|image|max:2048'
        ]);

        \$path = \$request->file('proof_file')->store('payments', 'public');

        \$payment = \App\Models\Payment::create([
            'invoice_id' => \$invoice->id,
            'user_id' => auth()->id(),
            'admin_id' => \$invoice->admin_id,
            'amount' => \$invoice->amount,
            'payment_date' => now(),
            'proof_file_path' => '/storage/' . \$path,
            'status' => 'menunggu_konfirmasi',
        ]);

        \$invoice->update(['status' => 'menunggu_konfirmasi']);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah.');
    }
}
PHP;

if (strpos($content, 'function uploadPayment') === false) {
    $content = preg_replace('/\}\s*$/', $uploadMethod, $content);
    file_put_contents($userControllerPath, $content);
}

echo "Methods restored!\n";
