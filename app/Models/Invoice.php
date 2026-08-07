<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class Invoice extends Model
{
    use HasAttachments, HasFactory, HasFilamentComments, HasOrganizationScope, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'type',
        'client_id',
        'supplier_id',
        'project_id',
        'purchase_order_id',
        'code',
        'number',
        'date',
        'due_date',
        'currency',
        'amount_net',
        'tax_amount',
        'amount_total',
        'status',
        'payment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'date' => 'date',
            'due_date' => 'date',
            'currency' => Currency::class,
            'amount_net' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'amount_total' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'payment_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(InvoiceStatusHistory::class)->latest('created_at');
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Pendiente
            && $this->due_date?->isPast();
    }
}
