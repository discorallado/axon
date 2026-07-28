<x-filament-panels::page>
    {{-- ── Cabecera de actividad ─────────────────────────────────────────────── --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <x-filament::badge :color="$this->activity->status->getColor()" :icon="$this->activity->status->getIcon()" size="lg">
                        {{ $this->activity->status->getLabel() }}
                    </x-filament::badge>
                </div>
                <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                    {{ $this->activity->name }}
                </h2>
                @if ($this->activity->description)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->activity->description }}</p>
                @endif
            </div>

            {{-- Fechas + progreso --}}
            <div class="flex items-center gap-6 text-sm text-gray-500 dark:text-gray-400">
                @if ($this->activity->start_date)
                    <div class="flex items-center gap-1">
                        <x-heroicon-m-arrow-right-circle class="h-4 w-4 shrink-0" />
                        {{ $this->activity->start_date->format('d/m/Y') }}
                    </div>
                @endif
                @if ($this->activity->end_date)
                    <div class="flex items-center gap-1">
                        <x-heroicon-m-flag class="h-4 w-4 shrink-0" />
                        {{ $this->activity->end_date->format('d/m/Y') }}
                    </div>
                @endif
                @php
                    $total = $this->activity->tasks->count();
                    $done  = $this->activity->tasks->filter(fn ($t) => $t->status->isCompleted())->count();
                @endphp
                <div class="flex items-center gap-1 font-medium text-gray-700 dark:text-gray-300">
                    <x-heroicon-m-check-circle class="h-4 w-4 shrink-0 text-success-500" />
                    {{ $done }}/{{ $total }} {{ __('tasks.plural') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tareas ────────────────────────────────────────────────────────────── --}}
    <div class="space-y-3">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('tasks.plural') }}</h3>

        @forelse ($this->activity->tasks as $task)
            @php
                $isFocus = $task->id === $this->focusTaskId;
                $initials = collect(explode(' ', $task->assignees->first()?->name ?? ''))
                    ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
            @endphp

            <div
                id="task-{{ $task->id }}"
                data-task-id="{{ $task->id }}"
                x-data="{ openComments: true }"
                x-init="{{ $isFocus ? 'setTimeout(() => { $el.scrollIntoView({ behavior: \'smooth\', block: \'center\' }); $el.classList.add(\'ring-2\', \'ring-primary-500\'); setTimeout(() => $el.classList.remove(\'ring-2\', \'ring-primary-500\'), 3000) }, 300)' : '' }}"
                class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 transition-all dark:bg-gray-900 dark:ring-white/10"
            >
                {{-- Fila de tarea --}}
                <div class="flex flex-wrap items-center gap-3 p-4">
                    {{-- Status badge --}}
                    <x-filament::badge :color="$task->status->getColor()" size="sm">
                        {{ $task->status->getLabel() }}
                    </x-filament::badge>

                    {{-- Código --}}
                    <span class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $task->code }}</span>

                    {{-- Nombre --}}
                    <span class="flex-1 text-sm font-medium text-gray-950 dark:text-white">{{ $task->name }}</span>

                    {{-- Prioridad --}}
                    <x-filament::badge :color="$task->priority->getColor()" size="sm">
                        {{ $task->priority->getLabel() }}
                    </x-filament::badge>

                    {{-- Fechas --}}
                    <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                        @if ($task->start_date)
                            <span class="flex items-center gap-1">
                                <x-heroicon-m-arrow-right-circle class="h-3 w-3" />
                                {{ $task->start_date->format('d/m/Y') }}
                            </span>
                        @endif
                        @if ($task->due_date)
                            <span @class([
                                'flex items-center gap-1',
                                'font-semibold text-danger-600 dark:text-danger-400' => $task->isOverdue(),
                            ])>
                                <x-heroicon-m-flag class="h-3 w-3" />
                                {{ $task->due_date->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>

                    {{-- Responsables --}}
                    @if ($task->assignees->isNotEmpty())
                        <div class="flex -space-x-1.5">
                            @foreach ($task->assignees->take(3) as $assignee)
                                @php
                                    $ini = collect(explode(' ', $assignee->name))->filter()->take(2)
                                        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
                                @endphp
                                <span
                                    title="{{ $assignee->name }}"
                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-gray-900"
                                >{{ $ini }}</span>
                            @endforeach
                            @if ($task->assignees->count() > 3)
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold text-gray-600 ring-2 ring-white dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-900">
                                    +{{ $task->assignees->count() - 3 }}
                                </span>
                            @endif
                        </div>
                    @endif

                    {{-- Toggle comentarios --}}
                    <button
                        type="button"
                        x-on:click="openComments = !openComments"
                        class="ml-auto flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    >
                        <x-heroicon-m-chat-bubble-left-ellipsis class="h-4 w-4" />
                        {{ $task->filamentComments()->count() }}
                    </button>
                </div>

                {{-- Descripción --}}
                @if ($task->description)
                    <div class="border-t border-gray-100 px-4 py-2 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        {{ $task->description }}
                    </div>
                @endif

                {{-- Comentarios inline --}}
                <div x-show="openComments" class="border-t border-gray-100 p-4 dark:border-gray-800">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                        {{ __('tasks.comments') }}
                    </p>
                    <livewire:filament-comments :model="$task" :key="'task-comments-'.$task->id" />
                </div>
            </div>
        @empty
            <div class="rounded-xl bg-white px-6 py-10 text-center text-sm text-gray-400 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                {{ __('tasks.empty') }}
            </div>
        @endforelse
    </div>

    {{-- ── Comentarios de la actividad ───────────────────────────────────────── --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="mb-4 text-base font-semibold text-gray-950 dark:text-white">
            {{ __('tasks.activity_comments') }}
        </h3>
        <livewire:filament-comments :model="$this->activity" :key="'activity-comments-'.$this->activity->id" />
    </div>
</x-filament-panels::page>
