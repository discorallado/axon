<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderStateMachine
{
    private const APPROVE_ROLES = ['super_admin', 'ingeniero'];

    private const RECEIVE_ROLES = ['super_admin', 'ingeniero', 'supervisor'];

    private const CANCEL_ROLES = ['super_admin', 'ingeniero'];

    /**
     * Transiciones permitidas por estado origen.
     * Estados terminales (recibida/anulada) no admiten más transiciones.
     */
    private const ALLOWED_TRANSITIONS = [
        'borrador' => ['emitida', 'anulada'],
        'emitida' => ['recibida', 'anulada'],
    ];

    public function canTransition(User $user, PurchaseOrder $purchaseOrder, PurchaseOrderStatus $toStatus): bool
    {
        $from = $purchaseOrder->status;

        if ($from === $toStatus || $from->isTerminal()) {
            return false;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];
        if (! in_array($toStatus->value, $allowed, true)) {
            return false;
        }

        return match ($toStatus) {
            PurchaseOrderStatus::Emitida => $user->hasAnyRole(self::APPROVE_ROLES),
            PurchaseOrderStatus::Recibida => $user->hasAnyRole(self::RECEIVE_ROLES),
            PurchaseOrderStatus::Anulada => $user->hasAnyRole(self::CANCEL_ROLES),
            default => false,
        };
    }

    public function transition(
        User $user,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderStatus $toStatus,
        ?string $comment = null
    ): void {
        if (! $this->canTransition($user, $purchaseOrder, $toStatus)) {
            abort(403, __('finance.errors.forbidden_status'));
        }

        DB::transaction(function () use ($user, $purchaseOrder, $toStatus, $comment) {
            $fromStatus = $purchaseOrder->status;

            $purchaseOrder->status = $toStatus;
            if ($toStatus === PurchaseOrderStatus::Emitida) {
                $purchaseOrder->approved_by = $user->id;
                $purchaseOrder->approved_at = now();
            }

            $purchaseOrder->save();

            PurchaseOrderStatusHistory::create([
                'organization_id' => $purchaseOrder->organization_id,
                'purchase_order_id' => $purchaseOrder->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $user->id,
                'comment' => $comment,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Retorna los estados a los que puede transicionar la OC desde su estado actual.
     */
    public function allowedNextStatuses(PurchaseOrder $purchaseOrder): array
    {
        if ($purchaseOrder->status->isTerminal()) {
            return [];
        }

        return array_map(
            fn (string $value) => PurchaseOrderStatus::from($value),
            self::ALLOWED_TRANSITIONS[$purchaseOrder->status->value] ?? []
        );
    }
}
