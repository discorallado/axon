<?php

namespace App\Models\Observers;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderObserver
{
    public function creating(PurchaseOrder $purchaseOrder): void
    {
        if (empty($purchaseOrder->organization_id) && Auth::check()) {
            $purchaseOrder->organization_id = Auth::user()->organization_id;
        }

        if (empty($purchaseOrder->code)) {
            $purchaseOrder->code = $this->generateCode($purchaseOrder);
        }
    }

    private function generateCode(PurchaseOrder $purchaseOrder): string
    {
        $year = now()->year;
        $prefix = 'OC';

        $last = PurchaseOrder::withoutGlobalScopes()
            ->where('organization_id', $purchaseOrder->organization_id)
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
