<?php

namespace App\Policies;

use App\Models\Tenancy;
use App\Models\User;

class TenancyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin']);
    }

    public function view(User $user, Tenancy $tenancy): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($user->role === 'admin') return $user->id === $tenancy->admin_id;
        return $user->id === $tenancy->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Tenancy $tenancy): bool
    {
        return $user->id === $tenancy->admin_id;
    }

    public function delete(User $user, Tenancy $tenancy): bool
    {
        return $user->id === $tenancy->admin_id;
    }
}
