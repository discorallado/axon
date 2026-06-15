<div>
    @if($submitted)
        {{-- Pantalla de confirmación ──────────────────────────────────────── --}}
        <div class="pf-card text-center py-12">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 rounded-full flex items-center justify-center"
                     style="background-color: rgb(var(--pf-600) / 0.15)">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="rgb(var(--pf-400))" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
            </div>

            <h2 class="text-xl font-semibold text-zinc-100 mb-2">
                ¡Solicitud enviada correctamente!
            </h2>
            <p class="text-zinc-400 mb-6">
                Hemos recibido tu solicitud. Nos pondremos en contacto a la brevedad.
            </p>

            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg"
                 style="background-color: rgb(var(--pf-600) / 0.1); border: 1px solid rgb(var(--pf-500) / 0.3)">
                <span class="text-sm text-zinc-400">Código de referencia:</span>
                <span class="font-mono font-semibold" style="color: rgb(var(--pf-300))">
                    {{ $referenceCode }}
                </span>
            </div>

            <p class="text-sm text-zinc-500 mt-6">
                Recibirás un correo de confirmación en <strong class="text-zinc-300">{{ $data['submitter_email'] ?? '' }}</strong>.
            </p>
        </div>
    @else
        {{-- Formulario Wizard ─────────────────────────────────────────────── --}}
        <div class="mb-8">
            <h1 class="pf-form-heading">Solicitud de Tableros Eléctricos</h1>
            <p class="pf-form-description">
                Complete los pasos a continuación para enviar su solicitud de cotización.
            </p>
        </div>

        <div class="pf-card">
            <form wire:submit="submit">
                {{ $this->form }}
            </form>
        </div>

        <x-filament-actions::modals />
    @endif
</div>
