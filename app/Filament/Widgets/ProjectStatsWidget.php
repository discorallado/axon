<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('super_admin');

        $projectQuery = Project::query();
        $taskQuery = Task::query();

        if (! $isSuperAdmin) {
            $projectQuery->whereHas('members', fn ($q) => $q->where('user_id', $user?->id));
            $taskQuery->whereHas('assignees', fn ($q) => $q->where('users.id', $user?->id));
        }

        $totalProjects = (clone $projectQuery)->count();
        $activeProjects = (clone $projectQuery)
            ->whereHas('status', fn ($q) => $q->where('is_completed', false))
            ->count();

        $pendingTasks = (clone $taskQuery)->where('status', TaskStatus::Pendiente)->count();
        $inProgressTasks = (clone $taskQuery)->where('status', TaskStatus::EnProgreso)->count();
        $completedTasks = (clone $taskQuery)->where('status', TaskStatus::Completada)->count();
        $totalTasks = (clone $taskQuery)->count();

        $overdueCount = (clone $taskQuery)
            ->where('due_date', '<', now())
            ->whereNotIn('status', [TaskStatus::Completada->value])
            ->count();

        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100)
            : 0;

        $projectLabel = $isSuperAdmin
            ? __('dashboard.stats.total_projects')
            : __('dashboard.stats.my_projects');

        $taskLabel = $isSuperAdmin
            ? __('dashboard.stats.pending_tasks')
            : __('dashboard.stats.my_tasks');

        return [
            Stat::make($projectLabel, $totalProjects)
                ->description(__('dashboard.stats.active_projects', ['count' => $activeProjects]))
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('primary')
                ->icon('heroicon-o-briefcase'),

            Stat::make($taskLabel, $pendingTasks)
                ->description(__('dashboard.stats.in_progress_tasks', ['count' => $inProgressTasks]))
                ->descriptionIcon('heroicon-o-play')
                ->color($pendingTasks > 20 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),

            Stat::make(__('dashboard.stats.task_completion'), $completionRate.'%')
                ->description(__('dashboard.stats.completed_tasks', ['count' => $completedTasks, 'total' => $totalTasks]))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($completionRate >= 75 ? 'success' : ($completionRate >= 40 ? 'warning' : 'danger'))
                ->icon('heroicon-o-chart-bar'),

            Stat::make(__('dashboard.stats.overdue_tasks'), $overdueCount)
                ->color($overdueCount > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
