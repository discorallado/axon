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
                } catch (e) {}
            }
            this.$watch('$wire.data', (value) => {
                if (this.$wire.submitted) return
                clearTimeout(this.saveTimer)
                this.saveTimer = setTimeout(() => this.saveDraft(value), 2000)
            }, { deep: true })
        },

        saveDraft(data) {
            const fileFields = ['load_list_file','unilineal_diagram','mechanical_plans','technical_specs','site_photos']
            const clean = Object.fromEntries(Object.entries(data).filter(([k]) => !fileFields.includes(k)))
            localStorage.setItem(this.draftKey, JSON.stringify({ data: clean, savedAt: new Date().toISOString() }))
        },

        restoreDraft() {
            const saved = localStorage.getItem(this.draftKey)
            if (!saved) return
            try {
                const parsed = JSON.parse(saved)
                this.$wire.set('data', { ...this.$wire.data, ...parsed.data })
                this.hasDraft = false
            } catch (e) {}
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
        {{-- Pantalla de confirmación --}}
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
            <h2 class="text-xl font-semibold text-zinc-100 mb-2">¡Solicitud enviada correctamente!</h2>
            <p class="text-zinc-400 mb-6">Hemos recibido tu solicitud. Nos pondremos en contacto a la brevedad.</p>
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg"
                 style="background-color: rgb(var(--pf-600) / 0.1); border: 1px solid rgb(var(--pf-500) / 0.3)">
                <span class="text-sm text-zinc-400">Código de referencia:</span>
                <span class="font-mono font-semibold" style="color: rgb(var(--pf-300))">{{ $referenceCode }}</span>
            </div>
            <p class="text-sm text-zinc-500 mt-6">
                Recibirás un correo de confirmación en
                <strong class="text-zinc-300">{{ $data['contact_email'] ?? '' }}</strong>.
            </p>
        </div>
    @else
        {{-- Toast de borrador (fixed top-right) --}}
        <div
            x-show="hasDraft"
            x-cloak
            x-transition:enter="transition  ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="fixed top-4 right-4 z-50 w-80 rounded-xl shadow-2xl p-4"
            style="background-color: #1b6136; border: 1px solid rgb(var(--pf-500) / 0.4);"
        >
            <div class="flex items-start gap-3 mb-3">
                <div class="shrink-0 mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center"
                     style="background-color: rgb(var(--pf-600) / 0.2)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="rgb(var(--pf-400))" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-zinc-100">Borrador guardado</p>
                    <p class="text-xs text-zinc-300 mt-0.5">
                        <span x-text="draftDate ? formatDate(draftDate) : ''"></span>
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    @click="restoreDraft()"
                    class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                    style="background-color: rgb(var(--pf-600) / 0.35); color: rgb(var(--pf-200)); border: 1px solid rgb(var(--pf-500) / 0.5);"
                    onmouseover="this.style.backgroundColor='rgb(var(--pf-600) / 0.55)'"
                    onmouseout="this.style.backgroundColor='rgb(var(--pf-600) / 0.35)'"
                >
                    Restaurar
                </button>
                <button
                    type="button"
                    @click="clearDraft()"
                    class="flex-1 py-1.5 rounded-lg text-xs font-medium transition-colors"
                    style="background-color: rgb(255 255 255 / 0.05); color: #a2a2ae; border: 1px solid rgb(255 255 255 / 0.08);"
                    onmouseover="this.style.color='#d4d4d8'"
                    onmouseout="this.style.color='#a2a2ae'"
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

        <p class="mt-3 text-xs text-zinc-500">(*) Campos obligatorios.</p>

        <x-filament-actions::modals />
    @endif
</div>
