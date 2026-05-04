<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header de la plantilla --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $record->code }}</h2>
                    <p class="text-gray-600 mt-1">{{ $record->name }}</p>
                    @if($record->description)
                        <p class="text-gray-500 text-sm mt-2">{{ $record->description }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <x-filament::badge :color="$record->is_active ? 'success' : 'gray'">
                        {{ $record->is_active ? 'Activo' : 'Inactivo' }}
                    </x-filament::badge>
                    <p class="text-sm text-gray-500 mt-2">{{ $record->category }}</p>
                </div>
            </div>
        </div>

        {{-- Estadísticas rápidas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Secciones</p>
                <p class="text-2xl font-bold text-gray-900">{{ $record->sections->count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Items Totales</p>
                <p class="text-2xl font-bold text-gray-900">{{ $record->items->count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Roles de Firma</p>
                <p class="text-2xl font-bold text-gray-900">{{ $record->roleSignatures->count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Ejecuciones</p>
                <p class="text-2xl font-bold text-gray-900">{{ $record->executions->count() }}</p>
            </div>
        </div>

        {{-- Vista jerárquica de items --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Estructura de Items</h3>
            </div>
            <div class="p-6">
                @foreach($record->sections as $section)
                    <div class="mb-6">
                        <div class="bg-gray-50 rounded-lg px-4 py-3 mb-3">
                            <h4 class="font-semibold text-gray-900">
                                <span class="text-primary-600">SECCIÓN {{ $section->code }}:</span>
                                {{ $section->title }}
                            </h4>
                            @if($section->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $section->description }}</p>
                            @endif
                        </div>

                        <div class="ml-4 space-y-2">
                            @php
                                $rootItems = $section->items()->whereNull('parent_id')->orderBy('order')->get();
                            @endphp

                            @foreach($rootItems as $item)
                                @include('filament.resources.fat-template-resource.pages._item-tree', ['item' => $item, 'depth' => 1])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Roles de firma --}}
        @if($record->roleSignatures->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Roles de Firma Requeridos</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($record->roleSignatures->sortBy('approval_order') as $role)
                            <div class="border rounded-lg p-4 {{ $role->is_required ? 'border-primary-500 bg-primary-50' : 'border-gray-200' }}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $role->role_display_name }}</p>
                                        <p class="text-sm text-gray-500">{{ $role->role_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <x-filament::badge :color="$role->signer_type === 'internal' ? 'info' : 'warning'">
                                            {{ $role->signer_type === 'internal' ? 'Interno' : 'Externo' }}
                                        </x-filament::badge>
                                        <p class="text-xs text-gray-500 mt-1">Orden: {{ $role->approval_order }}</p>
                                    </div>
                                </div>
                                @if(!$role->is_required)
                                    <p class="text-xs text-gray-500 mt-2">Opcional</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
