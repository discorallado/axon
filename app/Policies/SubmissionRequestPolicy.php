<?php

namespace App\Policies;

use App\Models\SubmissionRequest;
use App\Models\User;

class SubmissionRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'calidad']);
    }

    public function view(User $user, SubmissionRequest $submission): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'calidad'])
            && $user->organization_id === $submission->organization_id;
    }

    public function updateStatus(User $user, SubmissionRequest $submission): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])
            && $user->organization_id === $submission->organization_id;
    }

    public function assign(User $user, SubmissionRequest $submission): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor'])
            && $user->organization_id === $submission->organization_id;
    }

    public function export(User $user, SubmissionRequest $submission): bool
    {
        return $user->hasAnyRole(['super_admin', 'ingeniero', 'supervisor', 'calidad'])
            && $user->organization_id === $submission->organization_id;
    }

    public function reopen(User $user, SubmissionRequest $submission): bool
    {
        return $user->hasAnyRole(['super_admin'])
            && $user->organization_id === $submission->organization_id;
    }

    public function deleteAttachment(User $user, SubmissionRequest $submission): bool
    {
        if ($user->organization_id !== $submission->organization_id) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'ingeniero'])) {
            return true;
        }

        return $user->hasRole('supervisor') && $submission->assigned_to === $user->id;
    }
}
