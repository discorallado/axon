<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad']);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad'])
            && $project->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero']);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero'])
            && $project->organization_id === $user->organization_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole('super_admin')
            && $project->organization_id === $user->organization_id;
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasRole('super_admin');
    }
}
