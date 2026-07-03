<div wire:key="accordion-root-{{ $projectId }}" x-data
    x-on:axon-reorder-activities.window="$wire.reorderActivities($event.detail.ids)"
    x-on:axon-reorder-tasks.window="$wire.reorderTasks($event.detail.ids, $event.detail.activityId)">

    <x-filament::section>
        <x-slot name="heading">{{ __('tasks.activities.plural') }}</x-slot>

        <x-slot name="afterHeader">
            <x-filament::icon-button icon="heroicon-o-chevron-double-down" color="gray" size="sm"
                :tooltip="__('tasks.activities.actions.expand_all')"
                x-on:click="$dispatch('axon-expand-all')" />
            <x-filament::icon-button icon="heroicon-o-chevron-double-up" color="gray" size="sm"
                :tooltip="__('tasks.activities.actions.collapse_all')"
                x-on:click="$dispatch('axon-collapse-all')" />
        </x-slot>

        {{-- ── Activities Repeater ──────────────────────────────────────────────────── --}}
        <div class="axon-activities-list" id="activities-container-{{ $projectId }}">

            @forelse($activities as $activity)
                @php
                    $activityId = $activity->id;
                    $done = $activity->tasks->where('status', \App\Enums\TaskStatus::Completada)->count();
                    $total = $activity->tasks->count();
                @endphp

                {{-- ── Activity item ────────────────────────────────────────────────── --}}
                <div wire:key="activity-{{ $activityId }}" data-activity-id="{{ $activityId }}"
                    x-data="{
                        open: JSON.parse(localStorage.getItem('axon-act-{{ $projectId }}-{{ $activityId }}') ?? 'false'),
                        toggle() { this.open = !this.open; localStorage.setItem('axon-act-{{ $projectId }}-{{ $activityId }}', this.open); }
                    }"
                    x-on:axon-expand-all.window="open = true; localStorage.setItem('axon-act-{{ $projectId }}-{{ $activityId }}', true)"
                    x-on:axon-collapse-all.window="open = false; localStorage.setItem('axon-act-{{ $projectId }}-{{ $activityId }}', false)"
                    class="axon-activity-card axon-ring rounded-xl shadow-sm ring-1">
                    {{-- Item header --}}
                    <div class="axon-activity-header" @click="toggle()">
                        {{-- Drag grip — SPAN, no button, es el único handle de SortableJS --}}
                        <span class="axon-grip activity-drag-handle" @click.stop
                            title="{{ __('tasks.activities.actions.reorder') }}">
                            <x-heroicon-s-equals class="h-5 w-5" />
                        </span>

                        {{-- Chevron --}}
                        <x-heroicon-o-chevron-right
                            class="h-4 w-4 shrink-0 text-gray-950 transition-transform duration-150 dark:text-gray-500"
                            ::class="{ 'rotate-90': open }" />

                        {{-- Status badge --}}
                        <span class="shrink-0">
                            <x-filament::badge :color="$activity->status->getColor()" :icon="$activity->status->getIcon()" size="md">
                                {{ $activity->status->getLabel() }}
                            </x-filament::badge>
                        </span>

                        {{-- Nombre --}}
                        <span class="axon-activity-name">
                            {{ $activity->name }}
                        </span>

                        {{-- Descripción (solo pantallas grandes) --}}
                        @if ($activity->description)
                            <span class="hidden max-w-xs truncate text-md text-gray-400 dark:text-gray-500 lg:block">
                                {{ $activity->description }}
                            </span>
                        @endif

                        {{-- Progreso --}}
                        <span class="shrink-0 text-md text-gray-700 dark:text-gray-500">
                            {{ $done }}/{{ $total }} {{ __('tasks.plural') }}
                        </span>

                        {{-- Fechas inicio / fin --}}
                        <div class="flex w-24 shrink-0 flex-col text-right text-md leading-tight xl:w-48 xl:flex-row xl:items-center xl:justify-end xl:gap-3">
                            <div class="flex items-center justify-end gap-1 text-gray-400 dark:text-gray-500">
                                <x-heroicon-m-arrow-right-circle class="h-3 w-3 shrink-0" />
                                {{ $activity->start_date?->format('d/m/Y') ?? '—' }}
                            </div>
                            <div class="flex items-center justify-end gap-1 text-gray-400 dark:text-gray-500">
                                <x-heroicon-m-flag class="h-3 w-3 shrink-0" />
                                {{ $activity->end_date?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>

                        {{-- Acciones de actividad --}}
                        <div class="axon-activity-actions" @click.stop>
                            <x-filament::icon-button icon="heroicon-o-plus-circle" color="success" size="lg"
                                :tooltip="__('tasks.actions.create')"
                                x-on:click="$wire.mountAction('createTask', { activityId: '{{ $activityId }}' })" />
                            <x-filament::dropdown placement="bottom-end">
                                <x-slot name="trigger">
                                    <x-filament::icon-button icon="heroicon-o-ellipsis-vertical" color="gray"
                                        size="lg" />
                                </x-slot>
                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item icon="heroicon-o-pencil-square"
                                        x-on:click="close(); $wire.mountAction('editActivity', { activityId: '{{ $activityId }}' })">
                                        {{ __('tasks.activities.actions.edit') }}
                                    </x-filament::dropdown.list.item>
                                    <x-filament::dropdown.list.item icon="heroicon-o-trash" color="danger"
                                        x-on:click="close(); $wire.mountAction('deleteActivity', { activityId: '{{ $activityId }}' })">
                                        {{ __('tasks.activities.actions.delete') }}
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                        </div>
                    </div>

                    {{-- ── Tasks sub-repeater ────────────────────────────────────────── --}}
                    <div x-show="open" x-cloak class="axon-tasks-body" data-tasks-container
                        data-activity-id="{{ $activityId }}">
                        {{-- Lista de tareas --}}
                        <div class="axon-tasks-list p-3" data-tasks-list>

                            @forelse($activity->tasks as $task)
                                @php $taskId = $task->id; @endphp

                                <div wire:key="task-{{ $taskId }}" data-task-id="{{ $taskId }}"
                                    class="axon-task-row axon-ring ring-1">
                                    {{-- Drag grip — SPAN, no button --}}
                                    <span class="axon-grip task-drag-handle"
                                        title="{{ __('tasks.activities.actions.reorder') }}">
                                        <x-heroicon-s-equals class="h-4 w-4" />
                                    </span>

                                    {{-- Estado --}}
                                    <x-filament::badge :color="$task->status->getColor()" :icon="$task->status->getIcon()" size="sm"
                                        class="shrink-0">
                                        {{ $task->status->getLabel() }}
                                    </x-filament::badge>

                                    {{-- Código --}}
                                    <span
                                        class="hidden w-28 shrink-0 truncate font-mono text-md font-medium text-gray-400 dark:text-gray-500">
                                        {{ $task->code }}
                                    </span>

                                    {{-- Nombre --}}
                                    <span @class([
                                        'flex-1 truncate text-sm text-gray-950 dark:text-white',
                                        'line-through opacity-40' => $task->status->isCompleted(),
                                    ])>
                                        {{ $task->name }}
                                    </span>

                                    {{-- Prioridad --}}
                                    <span class="hidden shrink-0 sm:block">
                                        <x-filament::badge :color="$task->priority->getColor()" size="md">
                                            {{ $task->priority->getLabel() }}
                                        </x-filament::badge>
                                    </span>

                                    {{-- Responsables --}}
                                    <span
                                        class="hidden max-w-[140px] shrink-0 truncate text-md text-gray-400 dark:text-gray-500  xl:block">
                                        {{ $task->assignees->pluck('name')->join(', ') ?: '—' }}
                                    </span>

                                    {{-- Fechas inicio / límite --}}
                                    <div class="flex w-24 shrink-0 flex-col text-right text-md leading-tight xl:w-48 xl:flex-row xl:items-center xl:justify-end xl:gap-3">
                                        <div class="flex items-center justify-end gap-1 text-gray-400 dark:text-gray-500">
                                            <x-heroicon-m-arrow-right-circle class="h-3 w-3 shrink-0" />
                                            {{ $task->start_date?->format('d/m/Y') ?? '—' }}
                                        </div>
                                        <div @class([
                                            'flex items-center justify-end gap-1',
                                            'font-semibold text-danger-600 dark:text-danger-400' => $task->isOverdue(),
                                            'text-gray-400 dark:text-gray-500' => !$task->isOverdue(),
                                        ])>
                                            <x-heroicon-m-flag class="h-3 w-3 shrink-0" />
                                            {{ $task->due_date?->format('d/m/Y') ?? '—' }}
                                        </div>
                                    </div>

                                    {{-- Acciones de tarea --}}
                                    <div class="axon-task-actions">
                                        {{-- Dropdown: insertar + fechas --}}
                                        <x-filament::dropdown placement="bottom-end">
                                            <x-slot name="trigger">
                                                <x-filament::icon-button icon="heroicon-o-ellipsis-horizontal"
                                                    color="gray" size="md" />
                                            </x-slot>
                                            <x-filament::dropdown.list>
                                                <x-filament::dropdown.list.item icon="heroicon-o-arrow-up-on-square"
                                                    x-on:click="close(); $wire.mountAction('insertTask', { taskId: '{{ $taskId }}', position: 'before' })">
                                                    {{ __('tasks.actions.insert_before') }}
                                                </x-filament::dropdown.list.item>
                                                <x-filament::dropdown.list.item icon="heroicon-o-arrow-down-on-square"
                                                    x-on:click="close(); $wire.mountAction('insertTask', { taskId: '{{ $taskId }}', position: 'after' })">
                                                    {{ __('tasks.actions.insert_after') }}
                                                </x-filament::dropdown.list.item>
                                                <x-filament::dropdown.list.item icon="heroicon-o-calendar-days"
                                                    color="info"
                                                    x-on:click="close(); $wire.mountAction('scheduleDatesFromPrevious', { taskId: '{{ $taskId }}' })">
                                                    {{ __('tasks.actions.schedule_from_previous') }}
                                                </x-filament::dropdown.list.item>
                                            </x-filament::dropdown.list>
                                        </x-filament::dropdown>
                                        {{-- Editar --}}
                                        <x-filament::icon-button icon="heroicon-o-pencil-square" color="gray"
                                            size="md" :tooltip="__('tasks.actions.edit')"
                                            x-on:click="$wire.mountAction('editTask', { taskId: '{{ $taskId }}' })" />
                                        {{-- Eliminar --}}
                                        <x-filament::icon-button icon="heroicon-o-trash" color="danger" size="md"
                                            :tooltip="__('tasks.actions.delete')"
                                            x-on:click="$wire.mountAction('deleteTask', { taskId: '{{ $taskId }}' })" />
                                    </div>
                                </div>

                            @empty
                                <p class="py-3 text-center text-sm italic text-gray-400 dark:text-gray-500">
                                    {{ __('tasks.empty') }}
                                </p>
                            @endforelse

                        </div>

                        {{-- Botón "Nueva tarea" --}}
                        <div class="axon-add-task-trigger">
                            <x-filament::button icon="heroicon-o-plus" color="gray" size="md" outlined
                                class="w-full"
                                x-on:click="$wire.mountAction('createTask', { activityId: '{{ $activityId }}' })">
                                {{ __('tasks.actions.create') }}
                            </x-filament::button>
                        </div>
                    </div>

                </div>{{-- /activity item --}}

            @empty
                <p class="py-6 text-center text-sm italic text-gray-400 dark:text-gray-500">
                    {{ __('tasks.activities.empty') }}
                </p>
            @endforelse

        </div>{{-- /axon-activities-list --}}

        {{-- Botón "Nueva actividad" --}}
        <div class="axon-add-activity-trigger">
            <x-filament::button icon="heroicon-o-plus" color="gray" size="md" outlined class="w-full"
                x-on:click="$wire.mountAction('createActivity')">
                {{ __('tasks.activities.actions.create') }}
            </x-filament::button>
        </div>

        <x-filament-actions::modals />
    </x-filament::section>
</div>

@assets
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
@endassets

@script
    <script>
        let _activitySortable = null;
        const _taskSortables = [];
        let _reinitTimer = null;

        function destroySortables() {
            _activitySortable?.destroy();
            _activitySortable = null;
            _taskSortables.splice(0).forEach(s => s.destroy());
        }

        function initSortables() {
            destroySortables();

            // ── Actividades ───────────────────────────────────────────────────────
            const activityContainer = $el.querySelector('[id^="activities-container-"]');
            if (activityContainer) {
                _activitySortable = Sortable.create(activityContainer, {
                    animation: 150,
                    handle: '.activity-drag-handle',
                    onEnd() {
                        const ids = [...activityContainer.querySelectorAll(':scope > [data-activity-id]')]
                            .map(el => el.dataset.activityId);
                        window.dispatchEvent(new CustomEvent('axon-reorder-activities', {
                            detail: {
                                ids
                            }
                        }));
                    },
                });
            }

            // ── Tareas ────────────────────────────────────────────────────────────
            $el.querySelectorAll('[data-tasks-list]').forEach(tasksEl => {
                const s = Sortable.create(tasksEl, {
                    animation: 150,
                    handle: '.task-drag-handle',
                    group: 'axon-tasks',
                    onEnd(evt) {
                        const destContainer = evt.to.closest('[data-activity-id]');
                        const destIds = [...evt.to.querySelectorAll(':scope > [data-task-id]')]
                            .map(el => el.dataset.taskId);
                        window.dispatchEvent(new CustomEvent('axon-reorder-tasks', {
                            detail: { ids: destIds, activityId: destContainer?.dataset.activityId }
                        }));
                        if (evt.from !== evt.to) {
                            const srcContainer = evt.from.closest('[data-activity-id]');
                            const srcIds = [...evt.from.querySelectorAll(':scope > [data-task-id]')]
                                .map(el => el.dataset.taskId);
                            window.dispatchEvent(new CustomEvent('axon-reorder-tasks', {
                                detail: { ids: srcIds, activityId: srcContainer?.dataset.activityId }
                            }));
                        }
                    },
                });
                _taskSortables.push(s);
            });
        }

        initSortables();

        // Debounce: morph.updated puede dispararse muchas veces por render; reinit una sola vez
        Livewire.hook('morph.updated', ({
            el
        }) => {
            if (el === $el || $el.contains(el)) {
                clearTimeout(_reinitTimer);
                _reinitTimer = setTimeout(() => initSortables(), 80);
            }
        });
    </script>
@endscript
