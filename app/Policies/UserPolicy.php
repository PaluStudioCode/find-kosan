<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    public function view(User $user, User $model): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $model->id;
    }

    public function createLimited(User $user): bool
    {
        return in_array($user->role, ['pemilik_kos', 'super_admin']);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === 'super_admin';
    }
}
