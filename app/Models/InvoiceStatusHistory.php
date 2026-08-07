<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceStatusHistory extends Model
{
    use HasFactory, HasOrganizationScope, HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'from_status',
        'to_status',
        'changed_by',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'from_status' => InvoiceStatus::class,
            'to_status' => InvoiceStatus::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
