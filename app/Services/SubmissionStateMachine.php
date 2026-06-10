<?php

namespace App\Services;

use App\Models\SubmissionRequest;
use App\Models\SubmissionStatus;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmissionStateMachine
{
    /**
     * Roles que pueden avanzar el estado (nueva → en_revision → cotizada → aprobada).
     */
    private const ADVANCE_ROLES = ['super_admin', 'ingeniero', 'supervisor'];

    /**
     * Roles que pueden rechazar.
     */
    private const REJECT_ROLES = ['super_admin', 'supervisor'];

    /**
     * Roles que pueden reabrir un estado terminal.
     */
    private const REOPEN_ROLES = ['super_admin'];

    public function canTransition(User $user, SubmissionRequest $request, SubmissionStatus $toStatus): bool
    {
        $from = $request->status;

        if ($from->is_terminal) {
            return $user->hasAnyRole(self::REOPEN_ROLES)
                || ($user->hasRole('supervisor') && $user->can('reopen', $request));
        }

        if ($toStatus->slug === 'rechazada') {
            return $user->hasAnyRole(self::REJECT_ROLES);
        }

        return $user->hasAnyRole(self::ADVANCE_ROLES);
    }

    public function transition(
        User $user,
        SubmissionRequest $request,
        SubmissionStatus $toStatus,
        ?string $comment = null
    ): void {
        if (! $this->canTransition($user, $request, $toStatus)) {
            abort(403, __('submissions.errors.forbidden_status'));
        }

        DB::transaction(function () use ($user, $request, $toStatus, $comment) {
            $fromStatusId = $request->status_id;

            $request->update(['status_id' => $toStatus->id]);

            SubmissionStatusHistory::create([
                'organization_id' => $request->organization_id,
                'submission_request_id' => $request->id,
                'from_status_id' => $fromStatusId,
                'to_status_id' => $toStatus->id,
                'changed_by' => $user->id,
                'comment' => $comment,
                'created_at' => now(),
            ]);
        });
    }
}
