<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerWallet extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id', 'available_balance', 'pending_withdrawal_balance'];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'pending_withdrawal_balance' => 'decimal:2',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function transactions()
    {
        return $this->hasMany(OwnerWalletTransaction::class);
    }
}
