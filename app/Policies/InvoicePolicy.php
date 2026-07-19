<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($user->role === 'pemilik_kos') return $user->id === $invoice->owner_id;
        return $user->id === $invoice->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'pemilik_kos';
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->owner_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->owner_id;
    }
}
