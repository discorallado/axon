<?php

namespace App\Models\Observers;

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class InvoiceObserver
{
    public function creating(Invoice $invoice): void
    {
        if (empty($invoice->organization_id) && Auth::check()) {
            $invoice->organization_id = Auth::user()->organization_id;
        }

        if (empty($invoice->code)) {
            $invoice->code = $this->generateCode($invoice);
        }
    }

    private function generateCode(Invoice $invoice): string
    {
        $year = now()->year;
        $prefix = 'FC';

        $last = Invoice::withoutGlobalScopes()
            ->where('organization_id', $invoice->organization_id)
            ->where('code', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        return "{$prefix}-{$year}-".str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
