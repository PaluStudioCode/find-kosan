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
        if ($user->role === 'pemilik_kos') return $user->id === $payment->owner_id;
        return $user->id === $payment->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'penyewa';
    }

    public function review(User $user, Payment $payment): bool
    {
        return $user->id === $payment->owner_id;
    }
}
