<?php

namespace App\Models;

use App\Enums\ConditionalOperator;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormConditionalRule extends Model
{
    use HasFactory, HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'form_template_id',
        'template_version',
        'trigger_question_id',
        'operator',
        'trigger_value',
        'action',
        'target_type',
        'target_question_id',
        'target_section_id',
    ];

    protected function casts(): array
    {
        return [
            'operator' => ConditionalOperator::class,
            'template_version' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function triggerQuestion(): BelongsTo
    {
        return $this->belongsTo(FormQuestion::class, 'trigger_question_id');
    }

    public function targetQuestion(): BelongsTo
    {
        return $this->belongsTo(FormQuestion::class, 'target_question_id');
    }

    public function targetSection(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'target_section_id');
    }

    public function evaluate(mixed $answerValue): bool
    {
        return $this->operator->evaluate($answerValue, $this->trigger_value);
    }
}
