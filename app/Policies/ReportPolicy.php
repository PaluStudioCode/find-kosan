<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $report->reporter_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function handle(User $user, Report $report): bool
    {
        return $user->role === 'super_admin';
    }
}
