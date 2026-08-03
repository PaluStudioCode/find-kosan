<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id', 'amount', 'pph_percent', 'pph_amount', 'net_amount',
        'bank_name', 'account_number', 'account_holder_name', 'status',
        'owner_note', 'reviewed_by', 'reviewed_at', 'review_note', 'transferred_by',
        'transferred_at', 'transfer_reference', 'transfer_proof_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'pph_percent' => 'decimal:2',
        'pph_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
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
