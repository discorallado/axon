<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    private const VIEW_ROLES = ['super_admin', 'ingeniero', 'supervisor'];

    private const MANAGE_ROLES = ['super_admin', 'ingeniero'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::VIEW_ROLES);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasAnyRole(self::VIEW_ROLES)
            && $purchaseOrder->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::MANAGE_ROLES);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasAnyRole(self::MANAGE_ROLES)
            && $purchaseOrder->organization_id === $user->organization_id;
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole('super_admin')
            && $purchaseOrder->organization_id === $user->organization_id;
    }

    public function restore(User $user, PurchaseOrder $_purchaseOrder): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, PurchaseOrder $_purchaseOrder): bool
    {
        return $user->hasRole('super_admin');
    }

    public function changeStatus(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])
            && $purchaseOrder->organization_id === $user->organization_id;
    }
}
