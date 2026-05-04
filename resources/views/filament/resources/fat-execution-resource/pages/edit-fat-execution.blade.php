<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Formulario de edición --}}
        <x-filament::section>
            <x-slot name="heading">
                Editar Ejecución
            </x-slot>

            <form wire:submit="save" class="space-y-4">
                {{ $this->form }}

                <div class="flex justify-end gap-3">
                    <x-filament::button type="submit" color="primary">
                        Guardar Cambios
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Lista de revisiones --}}
        <x-filament::section>
            <x-slot name="heading">
                Revisiones
            </x-slot>

            @if($this->record->revisions->count() > 0)
                <div class="space-y-2">
                    @foreach($this->record->revisions as $revision)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <p class="font-medium">Versión {{ $revision->version }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Creada: {{ $revision->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <x-filament::badge :color="match($revision->status) {
                                'draft' => 'warning',
                                'approved' => 'success',
                                default => 'info'
                            }">
                                {{ $revision->status }}
                            </x-filament::badge>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay revisiones registradas.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
