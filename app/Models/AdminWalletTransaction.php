<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_wallet_id', 'invoice_id', 'withdrawal_request_id', 'type', 'amount', 'description',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function wallet()
    {
        return $this->belongsTo(AdminWallet::class, 'admin_wallet_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function withdrawalRequest()
    {
        return $this->belongsTo(WithdrawalRequest::class);
    }
}
