<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad']);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'tecnico', 'calidad'])
            && $task->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor']);
    }

    public function update(User $user, Task $task): bool
    {
        if (! ($task->organization_id === $user->organization_id)) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])) {
            return true;
        }

        // Técnico solo puede editar sus propias tareas asignadas
        if ($user->hasRole('tecnico')) {
            return $task->assignees()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])
            && $task->organization_id === $user->organization_id;
    }

    public function restore(User $user, Task $task): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $user->hasRole('super_admin');
    }
}
