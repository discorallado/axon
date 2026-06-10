<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FormSection;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FormSectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FormSection');
    }

    public function view(AuthUser $authUser, FormSection $formSection): bool
    {
        return $authUser->can('View:FormSection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FormSection');
    }

    public function update(AuthUser $authUser, FormSection $formSection): bool
    {
        return $authUser->can('Update:FormSection');
    }

    public function delete(AuthUser $authUser, FormSection $formSection): bool
    {
        return $authUser->can('Delete:FormSection');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FormSection');
    }

    public function restore(AuthUser $authUser, FormSection $formSection): bool
    {
        return $authUser->can('Restore:FormSection');
    }

    public function forceDelete(AuthUser $authUser, FormSection $formSection): bool
    {
        return $authUser->can('ForceDelete:FormSection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FormSection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FormSection');
    }

    public function replicate(AuthUser $authUser, FormSection $formSection): bool
    {
        return $authUser->can('Replicate:FormSection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FormSection');
    }
}
