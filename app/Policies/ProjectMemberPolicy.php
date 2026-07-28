<?php

namespace App\Policies;

use App\Models\ProjectMember;
use App\Models\User;

class ProjectMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad']);
    }

    public function view(User $user, ProjectMember $projectMember): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad'])
            && $projectMember->project->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero']);
    }

    public function update(User $user, ProjectMember $projectMember): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero'])
            && $projectMember->project->organization_id === $user->organization_id;
    }

    public function delete(User $user, ProjectMember $projectMember): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero'])
            && $projectMember->project->organization_id === $user->organization_id;
    }
}
