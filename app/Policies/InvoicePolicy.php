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
        if ($user->role === 'admin') return $user->id === $invoice->admin_id;
        return $user->id === $invoice->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->admin_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->admin_id;
    }
}
