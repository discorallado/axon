<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FatTemplateItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'parent_id',
        'code',
        'path',
        'description',
        'result_type',
        'result_config',
        'is_required',
        'allow_evidence',
        'depth',
        'order',
    ];

    protected $casts = [
        'result_config' => 'array',
        'is_required' => 'boolean',
        'allow_evidence' => 'boolean',
        'depth' => 'integer',
        'order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(FatTemplateSection::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FatTemplateItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FatTemplateItem::class, 'parent_id')->orderBy('order');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExecutionItemResult::class, 'template_item_id');
    }

    public function scopeDepth($query, int $depth)
    {
        return $query->where('depth', $depth);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getFullCodeAttribute(): string
    {
        return $this->path ?: $this->code;
    }

    public function isNumericType(): bool
    {
        return $this->result_type === 'numeric';
    }

    public function isTernaryType(): bool
    {
        return $this->result_type === 'ternary';
    }

    public function isTextType(): bool
    {
        return $this->result_type === 'text';
    }
}
