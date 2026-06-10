<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    use HasFactory, HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'view_type',
        'is_active',
        'current_version',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'current_version' => 'integer',
            'settings' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('sort_order');
    }

    public function currentSections(): HasMany
    {
        return $this->sections()->where('template_version', $this->current_version);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FormQuestion::class)->orderBy('sort_order');
    }

    public function currentQuestions(): HasMany
    {
        return $this->questions()->where('template_version', $this->current_version);
    }

    public function conditionalRules(): HasMany
    {
        return $this->hasMany(FormConditionalRule::class);
    }

    public function currentConditionalRules(): HasMany
    {
        return $this->conditionalRules()->where('template_version', $this->current_version);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SubmissionRequest::class);
    }

    public function publicUrl(): string
    {
        return route('public.form.show', $this->slug);
    }
}
