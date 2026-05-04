<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExecutionItemResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'revision_id',
        'template_item_id',
        'result',
        'observations',
        'numeric_value',
        'text_value',
        'has_evidence',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'numeric_value' => 'array',
        'has_evidence' => 'boolean',
    ];

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ExecutionRevision::class);
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(FatTemplateItem::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ExecutionEvidence::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ItemResultHistory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeResult($query, string $result)
    {
        return $query->where('result', $result);
    }

    public function scopeWithEvidence($query)
    {
        return $query->where('has_evidence', true);
    }

    public function getFormattedResultAttribute(): ?string
    {
        return match($this->result) {
            'C' => 'Conforme',
            'NC' => 'No Conforme',
            'NA' => 'No Aplica',
            default => null,
        };
    }

    public function getResultColorAttribute(): string
    {
        return match($this->result) {
            'C' => 'success', // verde
            'NC' => 'danger', // rojo
            'NA' => 'gray',   // gris
            default => 'warning',
        };
    }

    public function getResultSymbolAttribute(): string
    {
        return match($this->result) {
            'C' => '✓',
            'NC' => '✗',
            'NA' => '–',
            default => '○',
        };
    }

    public function isNonConformant(): bool
    {
        return $this->result === 'NC';
    }

    public function updateResult(string $result, ?int $userId = null): void
    {
        $oldValues = $this->getOriginal();
        
        $this->update([
            'result' => $result,
            'updated_by' => $userId,
        ]);

        // Registrar en historial si cambió
        if ($oldValues['result'] !== $result) {
            ItemResultHistory::create([
                'item_result_id' => $this->id,
                'changed_by' => $userId,
                'field_changed' => 'result',
                'old_value' => json_encode($oldValues['result']),
                'new_value' => json_encode($result),
            ]);
        }
    }
}
