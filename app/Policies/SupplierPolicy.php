<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    private const VIEW_ROLES = ['super_admin', 'ingeniero', 'supervisor'];

    private const MANAGE_ROLES = ['super_admin', 'ingeniero'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEW_ROLES);
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(self::VIEW_ROLES)
            && $supplier->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGE_ROLES);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasAnyRole(self::MANAGE_ROLES)
            && $supplier->organization_id === $user->organization_id;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('super_admin')
            && $supplier->organization_id === $user->organization_id;
    }

    public function restore(User $user, Supplier $_supplier): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Supplier $_supplier): bool
    {
        return $user->hasRole('super_admin');
    }
}
