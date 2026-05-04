<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExecutionRevision extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'execution_id',
        'version',
        'comments',
        'is_active',
        'snapshot_data',
        'created_by',
        'submitted_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_active' => 'boolean',
        'snapshot_data' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(FatExecution::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExecutionItemResult::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ExecutionSignature::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVersion($query, int $version)
    {
        return $query->where('version', $version);
    }

    public function getResultForItem(int $templateItemId): ?ExecutionItemResult
    {
        return $this->results()
            ->where('template_item_id', $templateItemId)
            ->first();
    }

    public function getCompletionPercentageAttribute(): float
    {
        $totalItems = $this->execution->template->items()->count();
        if ($totalItems === 0) {
            return 0.0;
        }

        $completedItems = $this->results()
            ->whereNotNull('result')
            ->count();

        return round(($completedItems / $totalItems) * 100, 2);
    }

    public function hasAllRequiredSignatures(): bool
    {
        $template = $this->execution->template;
        $requiredRoles = $template->roleSignatures()
            ->where('is_required', true)
            ->get();

        foreach ($requiredRoles as $role) {
            if (!$this->signatures()->where('role_signature_id', $role->id)->exists()) {
                return false;
            }
        }

        return true;
    }

    public function activate(): void
    {
        // Desactivar otras revisiones de la misma ejecución
        $this->execution->revisions()
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        // Activar esta revisión
        $this->update(['is_active' => true]);
    }
}
