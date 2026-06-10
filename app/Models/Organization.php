<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }

    public function submissionStatuses(): HasMany
    {
        return $this->hasMany(SubmissionStatus::class)->orderBy('sort_order');
    }

    public function submissionRequests(): HasMany
    {
        return $this->hasMany(SubmissionRequest::class);
    }
}
