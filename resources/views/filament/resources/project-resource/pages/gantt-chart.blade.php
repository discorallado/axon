<x-filament-panels::page>
    @if (empty($ganttTasks))
        <x-filament::section>
            <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-chart-bar" class="mx-auto mb-4 h-12 w-12 opacity-40" />
                <p class="text-sm">No hay tareas con fechas de inicio y término asignadas.</p>
                <p class="mt-1 text-xs opacity-70">Asigna fechas a las tareas del proyecto para visualizarlas aquí.</p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div
                x-data="{
                    gantt: null,
                    viewMode: 'Week',
                    init() {
                        this.loadFrappeGantt();
                    },
                    loadFrappeGantt() {
                        if (!document.querySelector('[data-frappe-gantt-css]')) {
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = 'https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css';
                            link.setAttribute('data-frappe-gantt-css', '');
                            document.head.appendChild(link);
                        }

                        if (typeof Gantt !== 'undefined') {
                            this.initGantt();
                            return;
                        }

                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.umd.js';
                        script.onload = () => this.initGantt();
                        document.body.appendChild(script);
                    },
                    initGantt() {
                        const tasks = @js($ganttTasks);
                        if (!tasks.length) return;

                        this.gantt = new Gantt(this.$refs.ganttContainer, tasks, {
                            view_mode: this.viewMode,
                            language: 'es',
                            popup: {
                                contents: (task) => `
                                    <div class='gantt-popup-wrapper'>
                                        <strong>${task.name}</strong><br>
                                        <span class='text-xs text-gray-500'>${task.group}</span><br>
                                        <span>${task.start} → ${task.end}</span>
                                    </div>
                                `,
                            },
                        });
                    },
                    changeView(mode) {
                        this.viewMode = mode;
                        if (this.gantt) {
                            this.gantt.change_view_mode(mode);
                        }
                    },
                }"
            >
                {{-- Controles de zoom --}}
                <div class="mb-4 flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Vista:</span>
                    <template x-for="mode in ['Day', 'Week', 'Month', 'Quarter Day', 'Half Day']" :key="mode">
                        <button
                            @click="changeView(mode)"
                            :class="viewMode === mode
                                ? 'bg-primary-600 text-white'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium transition dark:border-gray-600"
                            x-text="mode"
                        ></button>
                    </template>

                    <div class="ml-auto">
                        <x-filament::button
                            wire:click="loadGanttData"
                            color="gray"
                            size="sm"
                            icon="heroicon-o-arrow-path"
                        >
                            Actualizar
                        </x-filament::button>
                    </div>
                </div>

                {{-- Contenedor Gantt --}}
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <div x-ref="ganttContainer"></div>
                </div>

                <p class="mt-2 text-xs text-gray-400">
                    Mostrando {{ count($ganttTasks) }} tarea(s) con fechas asignadas.
                    Las tareas sin fechas no aparecen en esta vista.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
