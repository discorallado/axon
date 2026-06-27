<x-filament-panels::page>
    {{-- Filtros --}}
    <div class="flex flex-wrap gap-3 mb-4">
        <div class="w-56">
            <select
                wire:model.live="filterActivity"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">{{ __('projects.kanban.all_activities') }}</option>
                @foreach ($this->getActivitiesForFilter() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <select
                wire:model.live="filterPriority"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">{{ __('projects.kanban.all_priorities') }}</option>
                @foreach ($this->getPrioritiesForFilter() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tablero Kanban --}}
    <div class="flex gap-4 overflow-x-auto pb-4" id="kanban-board">
        @foreach ($this->getColumns() as $column)
            @php $status = $column['status']; @endphp
            <div class="flex-shrink-0 w-72" wire:key="column-{{ $status->value }}">
                <div class="rounded-xl bg-gray-100 dark:bg-gray-800 p-3 h-full">
                    {{-- Cabecera de columna --}}
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-filament::icon
                                :icon="$status->getIcon()"
                                class="w-4 h-4"
                                :style="'color: var(--color-' . $status->getColor() . '-500)'"
                            />
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ $status->getLabel() }}
                            </span>
                        </div>
                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            {{ $column['tasks']->count() }}
                        </span>
                    </div>

                    {{-- Tarjetas (zona sortable) --}}
                    <div
                        class="space-y-2 min-h-16 kanban-column"
                        data-status="{{ $status->value }}"
                        id="kanban-col-{{ $status->value }}"
                    >
                        @forelse ($column['tasks'] as $task)
                            <div
                                class="kanban-card rounded-lg bg-white dark:bg-gray-900 p-3 shadow-sm border border-gray-200 dark:border-gray-700 cursor-grab active:cursor-grabbing"
                                data-task-id="{{ $task->id }}"
                                wire:key="task-{{ $task->id }}"
                            >
                                {{-- Código y prioridad --}}
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-mono text-gray-400 dark:text-gray-500">
                                        {{ $task->code }}
                                    </span>
                                    <x-filament::icon
                                        :icon="$task->priority->getIcon()"
                                        class="w-3.5 h-3.5"
                                        :style="'color: var(--color-' . $task->priority->getColor() . '-500)'"
                                    />
                                </div>

                                {{-- Nombre --}}
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 line-clamp-2">
                                    {{ $task->name }}
                                </p>

                                {{-- Actividad --}}
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                                    {{ $task->activity->name }}
                                </p>

                                {{-- Pie: fecha y responsables --}}
                                <div class="flex items-center justify-between mt-2">
                                    @if ($task->due_date)
                                        <span class="text-xs {{ $task->isOverdue() ? 'text-danger-600 dark:text-danger-400 font-semibold' : 'text-gray-400 dark:text-gray-500' }}">
                                            {{ $task->due_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span></span>
                                    @endif

                                    @if ($task->assignees->isNotEmpty())
                                        <div class="flex -space-x-1">
                                            @foreach ($task->assignees->take(3) as $assignee)
                                                <span
                                                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-500 text-white text-[9px] font-bold ring-1 ring-white dark:ring-gray-900"
                                                    title="{{ $assignee->name }}"
                                                >
                                                    {{ strtoupper(substr($assignee->name, 0, 1)) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-gray-400 dark:text-gray-600">
                                {{ __('projects.kanban.empty_column') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    @endassets

    @script
    <script>
        function initKanban() {
            document.querySelectorAll('.kanban-column').forEach(function (col) {
                if (col._sortable) {
                    col._sortable.destroy();
                }
                col._sortable = Sortable.create(col, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'opacity-40',
                    dragClass: 'shadow-xl',
                    onEnd: function (evt) {
                        var taskId = evt.item.dataset.taskId;
                        var newStatus = evt.to.dataset.status;
                        if (taskId && newStatus) {
                            $wire.updateTaskStatus(taskId, newStatus);
                        }
                    },
                });
            });
        }

        initKanban();

        Livewire.hook('morph.updated', function () {
            initKanban();
        });
    </script>
    @endscript
</x-filament-panels::page>
