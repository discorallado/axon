<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceStateMachine
{
    private const MANAGE_ROLES = ['super_admin', 'ingeniero'];

    /**
     * Transiciones permitidas por estado origen.
     * `vencida` no es alcanzable por un usuario: la dispara el comando
     * programado (ver markOverdue()). Estados terminales (pagada/anulada)
     * no admiten más transiciones.
     */
    private const ALLOWED_TRANSITIONS = [
        'pendiente' => ['pagada', 'vencida', 'anulada'],
        'vencida' => ['pagada', 'anulada'],
    ];

    public function canTransition(User $user, Invoice $invoice, InvoiceStatus $toStatus): bool
    {
        $from = $invoice->status;

        if ($from === $toStatus || $from->isTerminal() || $toStatus === InvoiceStatus::Vencida) {
            return false;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$from->value] ?? [];
        if (! in_array($toStatus->value, $allowed, true)) {
            return false;
        }

        return $user->hasAnyRole(self::MANAGE_ROLES);
    }

    public function transition(User $user, Invoice $invoice, InvoiceStatus $toStatus, ?string $comment = null): void
    {
        if (! $this->canTransition($user, $invoice, $toStatus)) {
            abort(403, __('finance.errors.forbidden_status'));
        }

        DB::transaction(function () use ($user, $invoice, $toStatus, $comment) {
            $fromStatus = $invoice->status;

            $data = ['status' => $toStatus];
            if ($toStatus === InvoiceStatus::Pagada) {
                $data['payment_date'] = now()->toDateString();
            }

            $invoice->update($data);

            InvoiceStatusHistory::create([
                'organization_id' => $invoice->organization_id,
                'invoice_id' => $invoice->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $user->id,
                'comment' => $comment,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Transición automática disparada por el comando programado diario,
     * sin usuario asociado.
     */
    public function markOverdue(Invoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatus::Pendiente) {
            return;
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => InvoiceStatus::Vencida]);

            InvoiceStatusHistory::create([
                'organization_id' => $invoice->organization_id,
                'invoice_id' => $invoice->id,
                'from_status' => InvoiceStatus::Pendiente,
                'to_status' => InvoiceStatus::Vencida,
                'changed_by' => null,
                'comment' => 'Marcada automáticamente por vencimiento de plazo.',
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Retorna los estados a los que puede transicionar la factura desde su
     * estado actual, excluyendo `vencida` (solo alcanzable por el sistema).
     */
    public function allowedNextStatuses(Invoice $invoice): array
    {
        if ($invoice->status->isTerminal()) {
            return [];
        }

        return array_values(array_map(
            fn (string $value) => InvoiceStatus::from($value),
            array_filter(
                self::ALLOWED_TRANSITIONS[$invoice->status->value] ?? [],
                fn (string $value) => $value !== InvoiceStatus::Vencida->value
            )
        ));
    }
}
