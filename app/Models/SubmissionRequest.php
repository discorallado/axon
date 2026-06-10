<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionRequest extends Model
{
    use HasAttachments, HasComments, HasFactory, HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'form_template_id',
        'template_version',
        'reference_code',
        'status_id',
        'submitter_name',
        'submitter_email',
        'submitter_phone',
        'submitter_company',
        'ip_address',
        'user_agent',
        'submitted_at',
        'assigned_to',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SubmissionStatus::class, 'status_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class)->orderBy('id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SubmissionStatusHistory::class)->latest('created_at');
    }

    public function questionsForVersion(): Collection
    {
        return FormQuestion::withoutGlobalScopes()
            ->where('form_template_id', $this->form_template_id)
            ->where('template_version', $this->template_version)
            ->orderBy('sort_order')
            ->get();
    }

    public function sectionsForVersion(): Collection
    {
        return FormSection::withoutGlobalScopes()
            ->where('form_template_id', $this->form_template_id)
            ->where('template_version', $this->template_version)
            ->orderBy('sort_order')
            ->get();
    }
}
