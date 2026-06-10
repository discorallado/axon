<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionStatusHistory extends Model
{
    use HasFactory, HasOrganizationScope, HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'submission_request_id',
        'from_status_id',
        'to_status_id',
        'changed_by',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SubmissionRequest::class, 'submission_request_id');
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(SubmissionStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(SubmissionStatus::class, 'to_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
