@props(['item', 'depth'])

@php
    $resultTypeLabels = [
        'ternary' => 'C/NC/NA',
        'numeric' => 'Numérico',
        'text' => 'Texto',
    ];
    
    $resultTypeColors = [
        'ternary' => 'info',
        'numeric' => 'warning',
        'text' => 'gray',
    ];
@endphp

<div class="border rounded-lg {{ $depth > 1 ? 'ml-6 border-l-4 border-l-primary-300' : '' }}">
    <div class="p-3 {{ $depth > 1 ? 'bg-gray-50' : 'bg-white' }}">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-semibold text-primary-600">{{ $item->code }}</span>
                    @if($item->is_required)
                        <x-filament::badge color="danger" size="xs">Requerido</x-filament::badge>
                    @endif
                    @if($item->allow_evidence)
                        <x-filament::icon 
                            icon="heroicon-s-paper-clip" 
                            class="w-4 h-4 text-gray-400"
                            title="Permite evidencia"
                        />
                    @endif
                </div>
                <p class="text-gray-900 mt-1">{{ $item->description }}</p>
                <div class="flex items-center gap-3 mt-2">
                    <x-filament::badge :color="$resultTypeColors[$item->result_type] ?? 'gray'" size="xs">
                        {{ $resultTypeLabels[$item->result_type] ?? $item->result_type }}
                    </x-filament::badge>
                    @if($item->result_config)
                        <span class="text-xs text-gray-500">
                            @if($item->result_type === 'numeric' && is_array($item->result_config))
                                Rango: {{ $item->result_config['min'] ?? '?' }} - {{ $item->result_config['max'] ?? '?' }} {{ $item->result_config['unit'] ?? '' }}
                            @endif
                        </span>
                    @endif
                </div>
            </div>
            <div class="text-right text-xs text-gray-500">
                <span>Nivel {{ $item->depth }}</span>
            </div>
        </div>
    </div>

    {{-- Hijos recursivos --}}
    @if($item->children->count() > 0)
        <div class="border-t {{ $depth > 1 ? 'bg-gray-50' : '' }}">
            @foreach($item->children as $child)
                @include('filament.resources.fat-template-resource.pages._item-tree', [
                    'item' => $child, 
                    'depth' => $depth + 1
                ])
            @endforeach
        </div>
    @endif
</div>
