<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSection extends Model
{
    use HasFactory, HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'form_template_id',
        'template_version',
        'title',
        'description',
        'sort_order',
        'is_repeatable',
    ];

    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'sort_order' => 'integer',
            'is_repeatable' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FormQuestion::class)->orderBy('sort_order');
    }
}
