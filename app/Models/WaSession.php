<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaSession extends Model
{
    /**
     * Reserved admin_id for the SuperAdmin / system WhatsApp session.
     *
     * This session is shared globally (not owned by a single User), so
     * the `admin` relation will legitimately return null for it.
     */
    const SUPERADMIN_SESSION_ID = 0;

    protected $fillable = [
        'admin_id',
        'status',
        'phone_number',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    /**
     * The User that owns this WhatsApp session.
     *
     * NOTE: For the SuperAdmin/system session (admin_id = 0) there is no
     * matching user row, so this returns null. Always null-check the
     * result (e.g. `$session->admin?->name`).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Whether this is the shared SuperAdmin/system session (admin_id = 0).
     */
    public function isSuperAdminSession(): bool
    {
        return (int) $this->admin_id === self::SUPERADMIN_SESSION_ID;
    }

    /**
     * Scope to the SuperAdmin/system session.
     */
    public function scopeSuperAdminSession($query)
    {
        return $query->where('admin_id', self::SUPERADMIN_SESSION_ID);
    }
}
