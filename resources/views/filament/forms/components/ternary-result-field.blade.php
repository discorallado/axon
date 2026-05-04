@php
    $state = $getState();
    $color = match($state) {
        'C' => 'success',
        'NC' => 'danger',
        'NA' => 'gray',
        default => 'secondary',
    };
    
    $symbol = match($state) {
        'C' => '✓',
        'NC' => '✗',
        'NA' => '–',
        default => '○',
    };
    
    $label = match($state) {
        'C' => 'Conforme',
        'NC' => 'No Conforme',
        'NA' => 'No Aplica',
        default => 'Seleccionar',
    };
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="flex gap-2">
        <button
            type="button"
            wire:click="$set('{{ $getStatePath() }}', 'C')"
            @class([
                'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 border-2',
                'bg-green-50 border-green-500 text-green-700' => $state === 'C',
                'bg-white border-gray-200 text-gray-600 hover:border-green-300 hover:bg-green-50' => $state !== 'C',
            ])
        >
            <span class="text-lg">✓</span>
            <span class="ml-1">Conforme</span>
        </button>
        
        <button
            type="button"
            wire:click="$set('{{ $getStatePath() }}', 'NC')"
            @class([
                'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 border-2',
                'bg-red-50 border-red-500 text-red-700' => $state === 'NC',
                'bg-white border-gray-200 text-gray-600 hover:border-red-300 hover:bg-red-50' => $state !== 'NC',
            ])
        >
            <span class="text-lg">✗</span>
            <span class="ml-1">No Conf.</span>
        </button>
        
        <button
            type="button"
            wire:click="$set('{{ $getStatePath() }}', 'NA')"
            @class([
                'flex-1 px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 border-2',
                'bg-gray-100 border-gray-400 text-gray-700' => $state === 'NA',
                'bg-white border-gray-200 text-gray-600 hover:border-gray-400 hover:bg-gray-50' => $state !== 'NA',
            ])
        >
            <span class="text-lg">–</span>
            <span class="ml-1">N/A</span>
        </button>
        
        @if($state)
            <button
                type="button"
                wire:click="$set('{{ $getStatePath() }}', null)"
                class="px-2 py-2 text-xs text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded"
                title="Limpiar"
            >
                <x-heroicon-o-x-mark class="w-4 h-4" />
            </button>
        @endif
    </div>
    
    @if($state)
        <div class="mt-2 text-xs text-gray-500">
            Resultado actual: <strong>{{ $label }}</strong>
        </div>
    @endif
</x-dynamic-component>
