<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceStateMachine;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Marca como vencidas las facturas pendientes cuya fecha de vencimiento ya pasó';

    public function handle(InvoiceStateMachine $machine): int
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('status', InvoiceStatus::Pendiente)
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            $machine->markOverdue($invoice);
        }

        $this->info("Facturas marcadas como vencidas: {$invoices->count()}");

        return self::SUCCESS;
    }
}
