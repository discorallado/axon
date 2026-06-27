<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::whereHas('status', fn ($q) => $q->where('is_completed', false))->count();
        $completedProjects = Project::whereHas('status', fn ($q) => $q->where('is_completed', true))->count();

        $pendingTasks = Task::where('status', 'pendiente')->count();
        $inProgressTasks = Task::where('status', 'en_progreso')->count();
        $completedTasks = Task::where('status', 'completada')->count();
        $totalTasks = Task::count();

        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100)
            : 0;

        return [
            Stat::make(__('dashboard.stats.total_projects'), $totalProjects)
                ->description(__('dashboard.stats.active_projects', ['count' => $activeProjects]))
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('primary')
                ->icon('heroicon-o-briefcase'),

            Stat::make(__('dashboard.stats.pending_tasks'), $pendingTasks)
                ->description(__('dashboard.stats.in_progress_tasks', ['count' => $inProgressTasks]))
                ->descriptionIcon('heroicon-o-play')
                ->color($pendingTasks > 20 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),

            Stat::make(__('dashboard.stats.task_completion'), $completionRate.'%')
                ->description(__('dashboard.stats.completed_tasks', ['count' => $completedTasks, 'total' => $totalTasks]))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($completionRate >= 75 ? 'success' : ($completionRate >= 40 ? 'warning' : 'danger'))
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
