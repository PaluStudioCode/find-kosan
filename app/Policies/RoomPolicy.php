<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['pemilik_kos', 'super_admin']);
    }

    public function view(User $user, Room $room): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $room->boardingHouse->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'pemilik_kos';
    }

    public function update(User $user, Room $room): bool
    {
        return $user->id === $room->boardingHouse->owner_id;
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->id === $room->boardingHouse->owner_id;
    }
}
