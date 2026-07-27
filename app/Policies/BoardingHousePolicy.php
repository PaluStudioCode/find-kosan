<?php

namespace App\Policies;

use App\Models\BoardingHouse;
use App\Models\User;

class BoardingHousePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin']);
    }

    public function view(User $user, BoardingHouse $boardingHouse): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $boardingHouse->admin_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, BoardingHouse $boardingHouse): bool
    {
        return $user->id === $boardingHouse->admin_id;
    }

    public function delete(User $user, BoardingHouse $boardingHouse): bool
    {
        return $user->id === $boardingHouse->admin_id;
    }

    public function verify(User $user, BoardingHouse $boardingHouse): bool
    {
        return $user->role === 'super_admin';
    }
}
