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

    /**
     * Transiciones permitidas por estado origen.
     * Estados terminales (aprobada/rechazada) solo pueden reabrirse a 'nueva' por super_admin.
     */
    private const ALLOWED_TRANSITIONS = [
        'nueva' => ['en_revision', 'rechazada'],
        'en_revision' => ['cotizada', 'rechazada'],
        'cotizada' => ['aprobada', 'rechazada', 'en_revision'],
    ];

    public function canTransition(User $user, SubmissionRequest $request, SubmissionStatus $toStatus): bool
    {
        $from = $request->status;

        // Mismo estado: nunca permitido
        if ($from === $toStatus) {
            return false;
        }

        // Estados terminales: solo super_admin puede reabrir a 'nueva'
        if ($from->isTerminal()) {
            return $toStatus === SubmissionStatus::Nueva
                && $user->hasAnyRole(self::REOPEN_ROLES);
        }

        // Verificar que la transición esté en la tabla de transiciones válidas
        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];
        if (! in_array($toStatus->value, $allowed, true)) {
            return false;
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

    /**
     * Retorna los estados a los que puede transicionar la solicitud desde su estado actual.
     */
    public function allowedNextStatuses(SubmissionRequest $request): array
    {
        if ($request->status->isTerminal()) {
            return [SubmissionStatus::Nueva];
        }

        return array_map(
            fn (string $value) => SubmissionStatus::from($value),
            self::ALLOWED_TRANSITIONS[$request->status->value] ?? []
        );
    }
}
