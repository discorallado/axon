<x-filament-panels::page>
    {{-- Controles de zoom --}}
    <div class="flex items-center gap-2 mb-4">
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('projects.gantt.zoom') }}:</span>
        @foreach (['Day' => __('projects.gantt.day'), 'Week' => __('projects.gantt.week'), 'Month' => __('projects.gantt.month')] as $mode => $label)
            <button
                wire:click="$set('viewMode', '{{ $mode }}')"
                @class([
                    'px-3 py-1 text-xs font-medium rounded-full border transition-colors',
                    'bg-primary-500 text-white border-primary-500' => $viewMode === $mode,
                    'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary-400' => $viewMode !== $mode,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @php $tasks = $this->getGanttTasks(); @endphp

    @if (empty($tasks))
        <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-600">
            <x-filament::icon icon="heroicon-o-calendar-days" class="w-12 h-12 mb-3 opacity-40" />
            <p class="text-sm">{{ __('projects.gantt.no_tasks') }}</p>
        </div>
    @else
        <div
            class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4 overflow-x-auto"
            x-data="{
                gantt: null,
                viewMode: @js($viewMode),
                tasks: @js($tasks),
                init() {
                    this.gantt = new Gantt(this.$refs.ganttContainer, this.tasks, {
                        view_mode: this.viewMode,
                        language: 'es',
                        date_format: 'YYYY-MM-DD',
                        bar_height: 28,
                        padding: 18,
                        arrow_curve: 5,
                        on_click: function(task) {},
                    });
                },
            }"
            x-effect="if (gantt) { gantt.change_view_mode(viewMode) }"
            wire:ignore
        >
            <svg x-ref="ganttContainer" class="w-full"></svg>
        </div>
    @endif

    @assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">
    <script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
    @endassets

    {{-- Estilos oscuros para frappe-gantt --}}
    <style>
        .dark .gantt .grid-background { fill: #1f2937; }
        .dark .gantt .grid-header { fill: #111827; }
        .dark .gantt .grid-row { fill: #1f2937; }
        .dark .gantt .grid-row:nth-child(even) { fill: #111827; }
        .dark .gantt .row-line { stroke: #374151; }
        .dark .gantt .tick { stroke: #374151; }
        .dark .gantt .today-highlight { fill: #1e3a5f; opacity: 0.6; }
        .dark .gantt .bar-label { fill: #f9fafb; }
        .dark .gantt .lower-text, .dark .gantt .upper-text { fill: #9ca3af; }
        .dark .gantt .bar { fill: #3b82f6; }
        .dark .gantt .bar-progress { fill: #60a5fa; }
    </style>
</x-filament-panels::page>
