<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header con información de la ejecución --}}
        <div class="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm border dark:border-gray-700 dark:bg-gray-800">
            <div>
                <h3 class="text-lg font-semibold">{{ $execution->code }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $execution->project->name }} - {{ $execution->template->name }}</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Completitud</p>
                    <p class="text-2xl font-bold {{ $completionPercentage >= 100 ? 'text-success-600' : ($completionPercentage >= 50 ? 'text-warning-600' : 'text-danger-600') }}">
                        {{ number_format($completionPercentage, 1) }}%
                    </p>
                </div>
                <div class="w-32">
                    <x-filament::progressBar :value="$completionPercentage" :color="$completionPercentage >= 100 ? 'success' : ($completionPercentage >= 50 ? 'warning' : 'danger')" />
                </div>
            </div>
        </div>

        {{-- Barra de acciones --}}
        <div class="flex justify-end gap-3">
            @if($execution->status === 'draft' || $execution->status === 'pending_review')
                <x-filament::button wire:click="submitForReview" color="primary">
                    <x-heroicon-o-paper-airplane class="w-5 h-5 mr-2" />
                    Enviar a Revisión
                </x-filament::button>
            @endif
            
            @if($execution->latestRevision())
                <x-filament::button tag="a" :href="route('filament.admin.fat-executions.download-pdf', $execution)" color="gray">
                    <x-heroicon-o-document-arrow-down class="w-5 h-5 mr-2" />
                    Descargar PDF
                </x-filament::button>
            @endif
        </div>

        {{-- Checklist jerárquico --}}
        @if($sections->count() > 0)
            <div class="space-y-4">
                @foreach($sections as $section)
                    <div class="bg-white rounded-lg shadow-sm border dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                        {{-- Header de sección --}}
                        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b dark:border-gray-700">
                            <h4 class="font-semibold text-lg">{{ $section->code }}. {{ $section->title }}</h4>
                            @if($section->description)
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $section->description }}</p>
                            @endif
                        </div>

                        {{-- Items de la sección --}}
                        <div class="p-4 space-y-3">
                            @php
                                $sectionItems = $revision?->template?->items()
                                    ->where('path', 'like', $section->path . '.%')
                                    ->whereRaw("LENGTH(path) - LENGTH(REPLACE(path, '.', '')) = " . (substr_count($section->path, '.') + 1))
                                    ->orderBy('path')
                                    ->get() ?? collect();
                            @endphp

                            @forelse($sectionItems as $item)
                                @include('filament.resources.fat-execution-resource.pages._item-row', [
                                    'item' => $item,
                                    'result' => $revision?->itemResults?->firstWhere('template_item_id', $item->id),
                                    'depth' => substr_count($item->path, '.')
                                ])
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No hay items en esta sección</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white rounded-lg shadow-sm border dark:border-gray-700 dark:bg-gray-800">
                <x-heroicon-o-clipboard-document-list class="w-16 h-16 mx-auto text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No hay secciones configuradas</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">La plantilla no tiene secciones definidas.</p>
            </div>
        @endif

        {{-- Estado de guardado --}}
        <div 
            x-data="{ showing: false }"
            x-on:save-result.window="showing = true; setTimeout(() => showing = false, 2000)"
            x-show="showing"
            x-transition
            class="fixed bottom-4 right-4 bg-success-600 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2"
        >
            <x-heroicon-s-check-circle class="w-5 h-5" />
            <span>Guardado automáticamente</span>
        </div>
    </div>
</x-filament-panels::page>
