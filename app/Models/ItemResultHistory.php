<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemResultHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_result_id',
        'changed_by',
        'field_changed',
        'old_value',
        'new_value',
        'changed_from_ip',
    ];

    public function itemResult(): BelongsTo
    {
        return $this->belongsTo(ExecutionItemResult::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getFormattedOldValueAttribute(): mixed
    {
        return json_decode($this->old_value, true);
    }

    public function getFormattedNewValueAttribute(): mixed
    {
        return json_decode($this->new_value, true);
    }

    public function getChangeDescriptionAttribute(): string
    {
        $fieldLabels = [
            'result' => 'Resultado',
            'observations' => 'Observaciones',
            'numeric_value' => 'Valor Numérico',
            'text_value' => 'Valor de Texto',
        ];

        $field = $fieldLabels[$this->field_changed] ?? $this->field_changed;
        
        return "El campo '{$field}' fue modificado";
    }
}
