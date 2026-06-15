<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
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
        'from_status',
        'to_status',
        'changed_by',
        'comment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'from_status' => SubmissionStatus::class,
            'to_status' => SubmissionStatus::class,
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SubmissionRequest::class, 'submission_request_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
