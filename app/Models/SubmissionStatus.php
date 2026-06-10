<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionStatus extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'color',
        'sort_order',
        'is_initial',
        'is_terminal',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_initial' => 'boolean',
            'is_terminal' => 'boolean',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SubmissionRequest::class, 'status_id');
    }
}
