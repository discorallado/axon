<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class PurchaseOrder extends Model
{
    use HasAttachments, HasFactory, HasFilamentComments, HasOrganizationScope, HasUlids, SoftDeletes;

    /**
     * `status` queda fuera a propósito: solo debe cambiar vía
     * PurchaseOrderStateMachine::transition(), que lo asigna por propiedad
     * directa (no pasa por mass assignment). Así un `update(['status' => ...])`
     * arbitrario no puede saltarse las transiciones/roles permitidos.
     */
    protected $fillable = [
        'organization_id',
        'supplier_id',
        'project_id',
        'code',
        'number',
        'date',
        'currency',
        'amount_net',
        'tax_amount',
        'amount_total',
        'description',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'currency' => Currency::class,
            'amount_net' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'amount_total' => 'decimal:2',
            'status' => PurchaseOrderStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PurchaseOrderStatusHistory::class)->latest('created_at');
    }
}
