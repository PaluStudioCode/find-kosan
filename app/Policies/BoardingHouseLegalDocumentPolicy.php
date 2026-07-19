<?php

namespace App\Policies;

use App\Models\BoardingHouseLegalDocument;
use App\Models\User;

class BoardingHouseLegalDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['pemilik_kos', 'super_admin']);
    }

    public function view(User $user, BoardingHouseLegalDocument $boardingHouseLegalDocument): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $boardingHouseLegalDocument->boardingHouse->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'pemilik_kos';
    }

    public function update(User $user, BoardingHouseLegalDocument $boardingHouseLegalDocument): bool
    {
        return $user->id === $boardingHouseLegalDocument->boardingHouse->owner_id;
    }

    public function delete(User $user, BoardingHouseLegalDocument $boardingHouseLegalDocument): bool
    {
        return $user->id === $boardingHouseLegalDocument->boardingHouse->owner_id;
    }

    public function review(User $user, BoardingHouseLegalDocument $boardingHouseLegalDocument): bool
    {
        return $user->role === 'super_admin';
    }
}
