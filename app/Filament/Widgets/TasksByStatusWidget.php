<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Widgets\ChartWidget;

class TasksByStatusWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('dashboard.widgets.tasks_by_status');
    }

    protected function getData(): array
    {
        $colorHex = [
            'gray' => '#9ca3af',
            'info' => '#3b82f6',
            'warning' => '#f59e0b',
            'success' => '#22c55e',
            'danger' => '#ef4444',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach (TaskStatus::cases() as $status) {
            $count = Task::withoutGlobalScopes()
                ->where('organization_id', auth()->user()?->organization_id)
                ->where('status', $status)
                ->count();

            $labels[] = $status->getLabel();
            $data[] = $count;
            $colors[] = $colorHex[$status->getColor()] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
