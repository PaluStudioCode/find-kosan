<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'tenant_id', 'phone_number', 'message_type', 'message_body',
        'scheduled_date', 'status', 'sent_at', 'failed_reason', 'gateway_response',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'sent_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
