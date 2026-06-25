<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor']);
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])
            && $client->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasRole('super_admin')
            && $client->organization_id === $user->organization_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasRole('super_admin')
            && $client->organization_id === $user->organization_id;
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Client $client): bool
    {
        return $user->hasRole('super_admin');
    }
}
