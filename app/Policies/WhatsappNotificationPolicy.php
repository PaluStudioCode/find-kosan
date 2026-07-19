<?php

namespace App\Policies;

use App\Models\WhatsappNotification;
use App\Models\User;

class WhatsappNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WhatsappNotification $whatsappNotification): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($user->role === 'pemilik_kos') return $user->id === $whatsappNotification->invoice->owner_id;
        return $user->id === $whatsappNotification->tenant_id;
    }

    public function retry(User $user, WhatsappNotification $whatsappNotification): bool
    {
        return $user->id === $whatsappNotification->invoice->owner_id;
    }
}
