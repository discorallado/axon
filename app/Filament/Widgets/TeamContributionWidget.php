<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class TeamContributionWidget extends ChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.widgets.team_contribution');
    }

    protected function getData(): array
    {
        $orgId = auth()->user()?->organization_id;

        $users = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->withCount([
                'assignedTasks as created_count',
                'assignedTasks as completed_count' => fn ($q) => $q->where('status', 'completada'),
            ])
            ->orderByDesc('created_count')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widgets.tasks_created'),
                    'data' => $users->pluck('created_count')->all(),
                    'backgroundColor' => '#3b82f6',
                    'borderRadius' => 4,
                ],
                [
                    'label' => __('dashboard.widgets.tasks_completed'),
                    'data' => $users->pluck('completed_count')->all(),
                    'backgroundColor' => '#22c55e',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $users->map(fn ($u) => collect(explode(' ', $u->name))->take(2)->join(' '))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'top']],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
