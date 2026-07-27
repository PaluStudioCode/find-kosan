<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($user->role === 'admin') return $user->id === $payment->admin_id;
        return $user->id === $payment->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'user';
    }

    public function review(User $user, Payment $payment): bool
    {
        return $user->id === $payment->admin_id;
    }
}
