<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class TaskLink extends Model
{
    use HasOrganizationScope, HasUlids;

    protected $fillable = [
        'organization_id',
        'project_id',
        'source_id',
        'target_id',
        'type',
    ];

    protected function casts(): array
    {
        return ['type' => 'integer'];
    }
}
