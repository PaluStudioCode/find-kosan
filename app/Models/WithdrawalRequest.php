<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'amount', 'bank_name', 'account_number', 'account_holder_name', 'status',
        'owner_note', 'reviewed_by', 'reviewed_at', 'review_note', 'transferred_by',
        'transferred_at', 'transfer_reference', 'transfer_proof_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function transferer()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
