<?php

namespace App\Services;

use App\Enums\SubmissionStatus;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmissionStateMachine
{
    private const ADVANCE_ROLES = ['super_admin', 'ingeniero', 'supervisor'];

    private const REJECT_ROLES = ['super_admin', 'supervisor'];

    private const REOPEN_ROLES = ['super_admin'];

    public function canTransition(User $user, SubmissionRequest $request, SubmissionStatus $toStatus): bool
    {
        $from = $request->status;

        if ($from->isTerminal()) {
            return $user->hasAnyRole(self::REOPEN_ROLES)
                || ($user->hasRole('supervisor') && $user->can('reopen', $request));
        }

        if ($toStatus === SubmissionStatus::Rechazada) {
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
            $fromStatus = $request->status;

            $request->update(['status' => $toStatus]);

            SubmissionStatusHistory::create([
                'organization_id' => $request->organization_id,
                'submission_request_id' => $request->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $user->id,
                'comment' => $comment,
                'created_at' => now(),
            ]);
        });
    }
}
