<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminWallet extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'available_balance', 'pending_withdrawal_balance'];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'pending_withdrawal_balance' => 'decimal:2',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function transactions()
    {
        return $this->hasMany(AdminWalletTransaction::class);
    }
}
