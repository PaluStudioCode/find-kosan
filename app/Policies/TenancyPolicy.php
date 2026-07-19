<?php

namespace App\Policies;

use App\Models\Tenancy;
use App\Models\User;

class TenancyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['pemilik_kos', 'super_admin']);
    }

    public function view(User $user, Tenancy $tenancy): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($user->role === 'pemilik_kos') return $user->id === $tenancy->owner_id;
        return $user->id === $tenancy->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'pemilik_kos';
    }

    public function update(User $user, Tenancy $tenancy): bool
    {
        return $user->id === $tenancy->owner_id;
    }

    public function delete(User $user, Tenancy $tenancy): bool
    {
        return $user->id === $tenancy->owner_id;
    }
}
