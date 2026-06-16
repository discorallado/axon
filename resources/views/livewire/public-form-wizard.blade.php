<div
    x-data="{
        draftKey: 'axon_solicitud_draft',
        hasDraft: false,
        draftDate: null,
        saveTimer: null,

        init() {
            const saved = localStorage.getItem(this.draftKey)
            if (saved) {
                try {
                    const parsed = JSON.parse(saved)
                    if (parsed.data && Object.keys(parsed.data).some(k => parsed.data[k])) {
                        this.hasDraft = true
                        this.draftDate = parsed.savedAt ? new Date(parsed.savedAt) : null
                    }
                } catch {}
            }

            this.$watch('\$wire.data', (value) => {
                if (\$wire.submitted) return
                clearTimeout(this.saveTimer)
                this.saveTimer = setTimeout(() => this.saveDraft(value), 2000)
            }, { deep: true })
        },

        saveDraft(data) {
            const fileFields = ['load_list_file', 'unilineal_diagram', 'mechanical_plans', 'technical_specs', 'site_photos']
            const clean = Object.fromEntries(
                Object.entries(data).filter(([k]) => !fileFields.includes(k))
            )
            localStorage.setItem(this.draftKey, JSON.stringify({ data: clean, savedAt: new Date().toISOString() }))
        },

        restoreDraft() {
            const saved = localStorage.getItem(this.draftKey)
            if (!saved) return
            try {
                const parsed = JSON.parse(saved)
                \$wire.set('data', { ...\$wire.data, ...parsed.data })
                this.hasDraft = false
            } catch {}
        },

        clearDraft() {
            localStorage.removeItem(this.draftKey)
            this.hasDraft = false
        },

        formatDate(date) {
            if (!date) return ''
            return date.toLocaleString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
        }
    }"
    @draft-cleared.window="clearDraft()"
>
    @if($submitted)
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
                Recibirás un correo de confirmación en <strong class="text-zinc-300">{{ $data['contact_email'] ?? '' }}</strong>.
            </p>
        </div>
    @else
        {{-- Banner de borrador --}}
        <div
            x-show="hasDraft"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="mb-4 rounded-lg px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            style="background-color: rgb(var(--pf-600) / 0.12); border: 1px solid rgb(var(--pf-500) / 0.35)"
        >
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="rgb(var(--pf-400))" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-zinc-200">Tienes un formulario sin enviar</p>
                    <p class="text-xs text-zinc-400 mt-0.5">
                        Guardado el <span x-text="draftDate ? formatDate(draftDate) : ''"></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    @click="restoreDraft()"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                    style="background-color: rgb(var(--pf-600) / 0.3); color: rgb(var(--pf-200)); border: 1px solid rgb(var(--pf-500) / 0.4)"
                    onmouseover="this.style.backgroundColor='rgb(var(--pf-600) / 0.5)'"
                    onmouseout="this.style.backgroundColor='rgb(var(--pf-600) / 0.3)'"
                >
                    Restaurar borrador
                </button>
                <button
                    type="button"
                    @click="clearDraft()"
                    class="px-3 py-1.5 rounded-md text-sm font-medium text-zinc-400 hover:text-zinc-200 transition-colors"
                >
                    Descartar
                </button>
            </div>
        </div>

        {{-- Formulario --}}
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
