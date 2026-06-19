<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class SubmissionRequest extends Model
{
    use HasAttachments, HasFactory, HasFilamentComments, HasOrganizationScope, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'reference_code',
        'status',
        'project_name',
        'installation_location',
        'cost_center',
        'desired_delivery_date',
        'engineering_by',
        'submitter_name',
        'submitter_email',
        'submitter_phone',
        'submitter_company',
        'ip_address',
        'user_agent',
        'submitted_at',
        'assigned_to',
        'project_observations',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'desired_delivery_date' => 'date',
            'status' => SubmissionStatus::class,
            'raw_data' => 'array',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SubmissionStatusHistory::class)->latest('created_at');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubmissionItem::class)->orderBy('sort_order');
    }
}
