@props(['item', 'result' => null, 'depth' => 0])

<div class="border-b border-gray-100 dark:border-gray-700 last:border-0 pb-3 mb-3 last:mb-0">
    <div class="flex items-start gap-4" style="padding-left: {{ $depth * 20 }}px">
        {{-- Indicador de nivel --}}
        @if($depth > 0)
            <div class="flex-shrink-0 w-8 text-center text-xs text-gray-400 font-mono">
                {{ str_repeat('│', $depth) }}└─
            </div>
        @endif

        {{-- Contenido del item --}}
        <div class="flex-1 space-y-2">
            {{-- Header del item --}}
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
                            {{ $item->code }}
                        </span>
                        @if($item->is_required)
                            <span class="text-xs text-danger-600 dark:text-danger-400 font-medium">*</span>
                        @endif
                        @if($item->allow_evidence)
                            <x-heroicon-s-camera class="w-4 h-4 text-gray-400" title="Permite evidencias" />
                        @endif
                    </div>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                        {{ $item->description }}
                    </p>
                    @if($item->properties?->min_value !== null || $item->properties?->max_value !== null)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Rango: 
                            @if($item->properties?->min_value !== null) ≥ {{ $item->properties->min_value }} @endif
                            @if($item->properties?->max_value !== null) ≤ {{ $item->properties->max_value }} @endif
                            {{ $item->properties?->unit ?? '' }}
                        </p>
                    @endif
                </div>

                {{-- Selector de resultado --}}
                <div class="flex-shrink-0">
                    <div class="flex gap-1" x-data="{
                        value: '{{ $result?->result_value ?? '' }}',
                        update(value) {
                            this.value = value;
                            this.$dispatch('save-result', { 
                                templateItemId: {{ $item->id }}, 
                                data: { result_value: value } 
                            });
                        }
                    }">
                        <button
                            type="button"
                            @click="update('C')"
                            :class="value === 'C' ? 'bg-success-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-success-100 dark:hover:bg-success-900'"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1"
                            title="Conforme"
                        >
                            <x-heroicon-s-check-circle class="w-4 h-4" />
                            <span class="hidden sm:inline">C</span>
                        </button>
                        <button
                            type="button"
                            @click="update('NC')"
                            :class="value === 'NC' ? 'bg-danger-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-danger-100 dark:hover:bg-danger-900'"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1"
                            title="No Conforme"
                        >
                            <x-heroicon-s-x-circle class="w-4 h-4" />
                            <span class="hidden sm:inline">NC</span>
                        </button>
                        <button
                            type="button"
                            @click="update('NA')"
                            :class="value === 'NA' ? 'bg-gray-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1"
                            title="No Aplica"
                        >
                            <x-heroicon-s-minus-circle class="w-4 h-4" />
                            <span class="hidden sm:inline">NA</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Campo de observaciones --}}
            @if($result || in_array($result?->result_value ?? '', ['NC']))
                <div 
                    x-data="{ 
                        observations: '{{ $result?->observations ?? '' }}',
                        timeout: null,
                        save() {
                            clearTimeout(this.timeout);
                            this.timeout = setTimeout(() => {
                                this.$dispatch('save-result', { 
                                    templateItemId: {{ $item->id }}, 
                                    data: { observations: this.observations } 
                                });
                            }, 500);
                        }
                    }"
                    class="mt-2"
                >
                    <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Observaciones</label>
                    <textarea
                        x-model="observations"
                        @input="save()"
                        rows="2"
                        class="w-full text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                        placeholder="Agregar observaciones..."
                    ></textarea>
                </div>
            @endif

            {{-- Evidencias adjuntas --}}
            @if($item->allow_evidence && $result?->evidences?->count() > 0)
                <div class="mt-2">
                    <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Evidencias ({{ $result->evidences->count() }})</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($result->evidences as $evidence)
                            <div class="relative group">
                                @if(str_contains($evidence->file_path, '.pdf'))
                                    <x-heroicon-o-document class="w-10 h-10 text-gray-400" />
                                @else
                                    <img src="{{ Storage::disk('public')->url($evidence->file_path) }}" 
                                         alt="Evidencia" 
                                         class="w-10 h-10 object-cover rounded border dark:border-gray-600">
                                @endif
                                <a href="{{ Storage::disk('public')->url($evidence->file_path) }}" 
                                   target="_blank"
                                   class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded flex items-center justify-center">
                                    <x-heroicon-o-eye class="w-5 h-5 text-white" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Historial de cambios --}}
            @if($result?->history?->count() > 0)
                <details class="mt-2">
                    <summary class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-200">
                        Historial de cambios ({{ $result->history->count() }})
                    </summary>
                    <div class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                        @foreach($result->history->take(5) as $change)
                            <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-2 rounded">
                                <span class="font-medium">{{ $change->changed_by_name ?? 'Usuario' }}</span>
                                <span class="mx-1">→</span>
                                <span class="{{ $change->new_value === 'C' ? 'text-success-600' : ($change->new_value === 'NC' ? 'text-danger-600' : 'text-gray-500') }}">
                                    {{ $change->new_value ?? 'Sin valor' }}
                                </span>
                                <span class="text-gray-400 ml-2">{{ $change->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </div>
</div>
