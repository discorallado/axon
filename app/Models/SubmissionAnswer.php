<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAnswer extends Model
{
    use HasAttachments, HasFactory, HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'submission_request_id',
        'form_question_id',
        'question_key',
        'question_label',
        'value',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }

    public function submissionRequest(): BelongsTo
    {
        return $this->belongsTo(SubmissionRequest::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FormQuestion::class, 'form_question_id')->withoutGlobalScopes();
    }

    public function displayValue(): string
    {
        if ($this->value_json !== null) {
            return implode(', ', (array) $this->value_json);
        }

        return (string) ($this->value ?? '');
    }


}
