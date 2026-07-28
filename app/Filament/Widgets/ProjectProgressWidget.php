<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectProgressWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('dashboard.widgets.project_progress');
    }

    protected function getData(): array
    {
        $projects = Project::withoutGlobalScopes()
            ->where('organization_id', auth()->user()?->organization_id)
            ->whereHas('status', fn ($q) => $q->where('is_completed', false))
            ->with('tasks')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($projects as $project) {
            $pct = round($project->completionPercentage());
            $labels[] = $project->name;
            $data[] = $pct;
            $colors[] = match (true) {
                $pct >= 75 => '#22c55e',
                $pct >= 40 => '#f59e0b',
                default => '#3b82f6',
            };
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['beginAtZero' => true, 'max' => 100, 'ticks' => ['callback' => 'function(v){return v+"%"}']],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
