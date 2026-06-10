<?php

namespace App\Policies;

use App\Models\SubmissionStatus;
use App\Models\User;

class SubmissionStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, SubmissionStatus $status): bool
    {
        return $user->hasRole('super_admin')
            && $user->organization_id === $status->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, SubmissionStatus $status): bool
    {
        return $user->hasRole('super_admin')
            && $user->organization_id === $status->organization_id;
    }

    public function delete(User $user, SubmissionStatus $status): bool
    {
        return $user->hasRole('super_admin')
            && $user->organization_id === $status->organization_id
            && ! $status->is_initial;
    }
}
