<x-filament-panels::page>
    {{-- Filtros --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <div class="w-56">
            <select
                wire:model.live="filterActivity"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
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
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">{{ __('projects.kanban.all_priorities') }}</option>
                @foreach ($this->getPrioritiesForFilter() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tablero --}}
    <div class="flex gap-4 overflow-x-auto pb-4" id="kanban-board">
        @foreach ($this->getColumns() as $column)
            @php $status = $column['status']; @endphp
            <div
                class="w-[calc((100vw-10rem)/2)] min-w-[280px] flex-shrink-0 md:w-[calc((100vw-10rem)/3)] xl:w-[calc((100vw-10rem)/4)]"
                wire:key="column-{{ $status->value }}"
            >
                <div class="flex h-full flex-col rounded-xl bg-gray-100 p-3 dark:bg-gray-800">
                    {{-- Cabecera --}}
                    <div
                        class="mb-3 flex items-center justify-between rounded-lg px-3 py-2"
                        style="background-color: color-mix(in srgb, var(--color-{{ $status->getColor() }}-500, #6b7280) 15%, transparent)"
                    >
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-100">
                            {{ $status->getLabel() }}
                        </span>
                        <span
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold text-white"
                            style="background-color: var(--color-{{ $status->getColor() }}-500, #6b7280)"
                        >{{ $column['tasks']->count() }}</span>
                    </div>

                    {{-- Zona sortable --}}
                    <div
                        class="kanban-column min-h-16 flex-1 space-y-2"
                        data-status="{{ $status->value }}"
                        id="kanban-col-{{ $status->value }}"
                    >
                        @forelse ($column['tasks'] as $task)
                            @php
                                $viewUrl = \App\Filament\Resources\ProjectResource::getUrl(
                                    'view-activity',
                                    ['record' => $this->record->id, 'activity' => $task->activity_id]
                                ) . '?focus=' . $task->id;

                                $assignees  = $task->assignees;
                                $visible    = $assignees->take(2);
                                $overflow   = $assignees->count() - 2;

                                $avatarColors = ['#3b82f6','#8b5cf6','#ec4899','#f97316','#14b8a6','#f59e0b'];
                            @endphp

                            <div
                                class="kanban-card cursor-grab rounded-lg border border-gray-200 bg-white p-3 shadow-sm active:cursor-grabbing dark:border-gray-700 dark:bg-gray-900"
                                data-task-id="{{ $task->id }}"
                                wire:key="task-{{ $task->id }}"
                            >
                                {{-- Fila superior: código + prioridad + fecha --}}
                                <div class="mb-2 flex items-center gap-1.5">
                                    <span class="font-mono text-[11px] text-gray-400 dark:text-gray-500">
                                        {{ $task->code }}
                                    </span>

                                    @if ($task->priority)
                                        <span
                                            class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                            style="
                                                background-color: color-mix(in srgb, var(--color-{{ $task->priority->getColor() }}-500, #6b7280) 15%, transparent);
                                                color: var(--color-{{ $task->priority->getColor() }}-700, #374151);
                                            "
                                        >
                                            <x-dynamic-component :component="$task->priority->getIcon()" class="h-2.5 w-2.5" />
                                            {{ $task->priority->getLabel() }}
                                        </span>
                                    @endif

                                    <span class="ml-auto shrink-0">
                                        @if ($task->due_date)
                                            <span @class([
                                                'text-[11px]',
                                                'font-semibold text-danger-600 dark:text-danger-400' => $task->isOverdue(),
                                                'text-gray-400 dark:text-gray-500' => ! $task->isOverdue(),
                                            ])>
                                                {{ $task->due_date->isoFormat('D MMM') }}
                                            </span>
                                        @endif
                                    </span>
                                </div>

                                {{-- Nombre --}}
                                <p class="line-clamp-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $task->name }}
                                </p>

                                {{-- Descripción --}}
                                @if ($task->description)
                                    <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $task->description }}
                                    </p>
                                @endif

                                {{-- Pie: avatares + botón ver --}}
                                <div class="mt-3 flex items-center justify-between">
                                    <div class="flex items-center -space-x-1.5">
                                        @foreach ($visible as $i => $assignee)
                                            @php
                                                $initials = collect(explode(' ', $assignee->name))
                                                    ->filter()->take(2)
                                                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                                                    ->join('');
                                                $bg = $avatarColors[$i % count($avatarColors)];
                                            @endphp
                                            <span
                                                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-white text-[10px] font-bold text-white dark:border-gray-900"
                                                style="background-color: {{ $bg }}"
                                                title="{{ $assignee->name }}"
                                            >{{ $initials }}</span>
                                        @endforeach

                                        @if ($overflow > 0)
                                            <span
                                                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-white bg-gray-300 text-[10px] font-bold text-gray-700 dark:border-gray-900 dark:bg-gray-600 dark:text-gray-200"
                                                title="{{ $assignees->slice(2)->pluck('name')->join(', ') }}"
                                            >+{{ $overflow }}</span>
                                        @endif

                                        @if ($assignees->isEmpty())
                                            <span class="text-xs text-gray-300 dark:text-gray-600">
                                                <x-heroicon-o-user class="h-4 w-4" />
                                            </span>
                                        @endif
                                    </div>

                                    <a
                                        href="{{ $viewUrl }}"
                                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                        title="{{ __('tasks.actions.view') }}"
                                        wire:navigate
                                    >
                                        <x-heroicon-m-eye class="h-4 w-4" />
                                    </a>
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
                if (col._sortable) col._sortable.destroy();
                col._sortable = Sortable.create(col, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'opacity-40',
                    dragClass: 'shadow-xl',
                    onEnd: function (evt) {
                        const taskId   = evt.item.dataset.taskId;
                        const newStatus = evt.to.dataset.status;
                        if (taskId && newStatus) $wire.updateTaskStatus(taskId, newStatus);
                    },
                });
            });
        }

        initKanban();
        Livewire.hook('morph.updated', () => initKanban());
    </script>
    @endscript
</x-filament-panels::page>
