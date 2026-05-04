<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FatTemplateSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'code',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(FatTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FatTemplateItem::class)->orderBy('order');
    }

    public function parentItems(): HasMany
    {
        return $this->hasMany(FatTemplateItem::class)
            ->whereNull('parent_id')
            ->orderBy('order');
    }
}
