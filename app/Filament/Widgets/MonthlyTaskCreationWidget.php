<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyTaskCreationWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('dashboard.widgets.monthly_task_creation');
    }

    protected function getData(): array
    {
        $orgId = auth()->user()?->organization_id;
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        $rows = Task::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('COUNT(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month');

        $data = $months->map(fn ($m) => $rows->get($m, 0))->values()->all();
        $labels = $months->map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->locale('es')->isoFormat('MMM YY'))->values()->all();

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widgets.tasks_created'),
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
