<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappNotification;

class WhatsappNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WhatsappNotification $whatsappNotification): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }
        if ($user->role === 'admin') {
            return $user->id === $whatsappNotification->invoice->admin_id;
        }

        return $user->id === $whatsappNotification->user_id;
    }

    public function retry(User $user, WhatsappNotification $whatsappNotification): bool
    {
        return $user->id === $whatsappNotification->invoice->admin_id;
    }
}
