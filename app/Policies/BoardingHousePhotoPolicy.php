<?php

namespace App\Policies;

use App\Models\BoardingHousePhoto;
use App\Models\User;

class BoardingHousePhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin']);
    }

    public function view(User $user, BoardingHousePhoto $boardingHousePhoto): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->id === $boardingHousePhoto->boardingHouse->admin_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, BoardingHousePhoto $boardingHousePhoto): bool
    {
        return $user->id === $boardingHousePhoto->boardingHouse->admin_id;
    }

    public function delete(User $user, BoardingHousePhoto $boardingHousePhoto): bool
    {
        return $user->id === $boardingHousePhoto->boardingHouse->admin_id;
    }
}
