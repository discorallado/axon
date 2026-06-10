<?php

namespace App\Policies;

use App\Models\FormTemplate;
use App\Models\User;

class FormTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor']);
    }

    public function view(User $user, FormTemplate $template): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])
            && $user->organization_id === $template->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero']);
    }

    public function update(User $user, FormTemplate $template): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero'])
            && $user->organization_id === $template->organization_id;
    }

    public function delete(User $user, FormTemplate $template): bool
    {
        return $user->hasRole('super_admin')
            && $user->organization_id === $template->organization_id;
    }
}
