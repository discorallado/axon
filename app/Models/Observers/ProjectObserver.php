<?php

namespace App\Models\Observers;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function creating(Project $project): void
    {
        if (empty($project->organization_id) && Auth::check()) {
            $project->organization_id = Auth::user()->organization_id;
        }

        if (empty($project->code)) {
            $project->code = $this->generateCode($project);
        }
    }

    private function generateCode(Project $project): string
    {
        $prefix = strtoupper($project->code_prefix ?? 'PROJ');
        $year = now()->year;

        $last = Project::withoutGlobalScopes()
            ->where('organization_id', $project->organization_id)
            ->where('code', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        return "{$prefix}-{$year}-".str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
