<?php

namespace App\Policies;

use App\Models\BoardingHouse;
use App\Models\User;

class BoardingHousePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['pemilik_kos', 'super_admin']);
    }

    public function view(User $user, BoardingHouse $boardingHouse): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $boardingHouse->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'pemilik_kos';
    }

    public function update(User $user, BoardingHouse $boardingHouse): bool
    {
        return $user->id === $boardingHouse->owner_id;
    }

    public function delete(User $user, BoardingHouse $boardingHouse): bool
    {
        return $user->id === $boardingHouse->owner_id;
    }

    public function verify(User $user, BoardingHouse $boardingHouse): bool
    {
        return $user->role === 'super_admin';
    }
}
