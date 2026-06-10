<?php

namespace App\Models;

use App\Enums\FormQuestionType;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormQuestion extends Model
{
    use HasFactory, HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'form_template_id',
        'form_section_id',
        'template_version',
        'key',
        'label',
        'type',
        'options',
        'placeholder',
        'help_text',
        'is_required',
        'sort_order',
        'validation_rules',
    ];

    protected function casts(): array
    {
        return [
            'type' => FormQuestionType::class,
            'options' => 'array',
            'validation_rules' => 'array',
            'is_required' => 'boolean',
            'template_version' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }

    public function triggeredRules(): HasMany
    {
        return $this->hasMany(FormConditionalRule::class, 'trigger_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class);
    }
}
