<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad']);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad'])
            && $activity->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero']);
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])
            && $activity->organization_id === $user->organization_id;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero'])
            && $activity->organization_id === $user->organization_id;
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return $user->hasRole('super_admin');
    }
}
