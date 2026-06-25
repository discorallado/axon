<?php

namespace App\Models\Observers;

use App\Models\Task;

class TaskObserver
{
    public function creating(Task $task): void
    {
        if (empty($task->code)) {
            $task->code = $this->generateCode($task);
        }
    }

    private function generateCode(Task $task): string
    {
        $activity = $task->activity()->withoutGlobalScopes()->first();
        $project = $activity?->project()->withoutGlobalScopes()->first();

        $prefix = strtoupper($project?->code_prefix ?? 'T');
        $activityOrder = str_pad($activity?->order ?? 0, 3, '0', STR_PAD_LEFT);

        $last = Task::withoutGlobalScopes()
            ->where('activity_id', $task->activity_id)
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if ($last && preg_match('/T(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return "{$prefix}-{$activityOrder}-T".str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
