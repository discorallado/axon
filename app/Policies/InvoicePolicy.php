<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    private const VIEW_ROLES = ['super_admin', 'ingeniero', 'supervisor'];

    private const MANAGE_ROLES = ['super_admin', 'ingeniero'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEW_ROLES);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(self::VIEW_ROLES)
            && $invoice->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGE_ROLES);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(self::MANAGE_ROLES)
            && $invoice->organization_id === $user->organization_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('super_admin')
            && $invoice->organization_id === $user->organization_id;
    }

    public function restore(User $user, Invoice $_invoice): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Invoice $_invoice): bool
    {
        return $user->hasRole('super_admin');
    }

    public function changeStatus(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(self::MANAGE_ROLES)
            && $invoice->organization_id === $user->organization_id;
    }
}
