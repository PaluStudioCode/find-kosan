<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_wallet_id', 'invoice_id', 'withdrawal_request_id', 'type', 'amount', 'description',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function wallet()
    {
        return $this->belongsTo(OwnerWallet::class, 'owner_wallet_id');
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
