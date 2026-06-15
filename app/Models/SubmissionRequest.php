<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasOrganizationScope;
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
        'reference_code',
        'status',
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
            'submitted_at' => 'datetime',
            'status' => SubmissionStatus::class,
        ];
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
}
