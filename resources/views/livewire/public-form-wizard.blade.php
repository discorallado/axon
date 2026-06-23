<div
    x-data="{
        draftKey: 'axon_solicitud_draft_v2',
        hasDraft: false,
        draftDate: null,
        saveTimer: null,

        init() {
            const saved = localStorage.getItem(this.draftKey)
            if (saved) {
                try {
                    const parsed = JSON.parse(saved)
                    if (parsed && (Object.keys(parsed.data || {}).some(k => parsed.data[k]) || (parsed.items || []).length > 0)) {
                        this.hasDraft = true
                        this.draftDate = parsed.savedAt ? new Date(parsed.savedAt) : null
                    }
                } catch (e) {}
            }
            this.$watch('$wire.data', (value) => {
                if (this.$wire.submitted) return
                clearTimeout(this.saveTimer)
                this.saveTimer = setTimeout(() => this.saveDraft(), 2000)
            }, { deep: true })
            this.$watch('$wire.items', (value) => {
                if (this.$wire.submitted) return
                clearTimeout(this.saveTimer)
                this.saveTimer = setTimeout(() => this.saveDraft(), 2000)
            }, { deep: true })
            // Watch in-progress tablero modal data (synced after each step navigation)
            this.$watch('$wire.mountedActionsData', (value) => {
                if (this.$wire.submitted) return
                clearTimeout(this.saveTimer)
                this.saveTimer = setTimeout(() => this.saveDraft(), 1500)
            }, { deep: true })
            // When the modal closes, re-save without pendingItem
            this.$watch('$wire.mountedActions', (value) => {
                if (this.$wire.submitted) return
                if (!value || value.length === 0) {
                    clearTimeout(this.saveTimer)
                    this.saveTimer = setTimeout(() => this.saveDraft(), 500)
                }
            })
        },

        saveDraft() {
            const fileFields = ['technical_specs', 'site_photos']
            const rawData = this.$wire.data || {}
            const cleanData = Object.fromEntries(Object.entries(rawData).filter(([k]) => !fileFields.includes(k)))

            const itemFileFields = ['load_list_file', 'unilineal_diagram', 'mechanical_plans']
            const cleanItems = (this.$wire.items || []).map(item => {
                return Object.fromEntries(Object.entries(item).filter(([k]) => !itemFileFields.includes(k)))
            })

            const draft = { data: cleanData, items: cleanItems, savedAt: new Date().toISOString() }

            // Persist in-progress tablero data while the modal is open
            const actionName = (this.$wire.mountedActions || [])[0]
            const actionData = (this.$wire.mountedActionsData || [])[0]
            if (actionName === 'tablero' && actionData) {
                const cleanActionData = Object.fromEntries(
                    Object.entries(actionData).filter(([k]) => !itemFileFields.includes(k))
                )
                if (Object.values(cleanActionData).some(v => v !== null && v !== undefined && v !== '' && v !== false)) {
                    draft.pendingItem = cleanActionData
                }
            }

            localStorage.setItem(this.draftKey, JSON.stringify(draft))
        },

        async restoreDraft() {
            const saved = localStorage.getItem(this.draftKey)
            if (!saved) return
            try {
                const parsed = JSON.parse(saved)
                if (parsed.data) {
                    await this.$wire.set('data', { ...this.$wire.data, ...parsed.data })
                }
                if (Array.isArray(parsed.items)) {
                    await this.$wire.set('items', parsed.items)
                }
                if (parsed.pendingItem && Object.values(parsed.pendingItem).some(v => v !== null && v !== undefined && v !== '' && v !== false)) {
                    await this.$wire.set('pendingItemData', parsed.pendingItem)
                }
                this.hasDraft = false
            } catch (e) {}
        },

        clearDraft() {
            localStorage.removeItem(this.draftKey)
            this.hasDraft = false
        },

        formatDate(date) {
            if (!date) return ''
            return date.toLocaleString('es-CL', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            })
        }
    }"
    @draft-cleared.window="clearDraft()">

    @if($submitted)
    {{-- Pantalla de confirmación --}}
    <div class="pf-card shadow-2xl text-center py-12">
        <div class="flex justify-center mb-6">
            <div class="w-16 h-16 rounded-full flex items-center justify-center"
                style="background-color: rgb(var(--pf-600) / 0.15)">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="rgb(var(--pf-500))" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>
        <h2 class="text-xl font-semibold text-zinc-900 mb-2">¡Solicitud enviada correctamente!</h2>
        <p class="text-zinc-500 mb-6">Hemos recibido tu solicitud. Nos pondremos en contacto a la brevedad.</p>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg"
            style="background-color: rgb(var(--pf-600) / 0.1); border: 1px solid rgb(var(--pf-500) / 0.3)">
            <span class="text-sm text-zinc-500">Código de referencia:</span>
            <span class="font-mono font-semibold pf-reference-code">{{ $referenceCode }}</span>
        </div>
        <p class="text-sm text-zinc-400 mt-6">
            Recibirás un correo de confirmación en
            <strong class="text-zinc-700">{{ $data['contact_email'] ?? '' }}</strong>.
        </p>
    </div>
    @else
    {{-- Toast de borrador --}}
    <div
        x-show="hasDraft"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-4"
        class="fixed top-4 right-4 z-50 w-80 rounded-xl bg-white border border-zinc-200 shadow-xl p-4">
        <div class="flex items-start gap-3 mb-3">
            <div class="shrink-0 mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center"
                style="background-color: rgb(var(--pf-600) / 0.1)">
                <svg class="w-4 h-4" style="color: rgb(var(--pf-600))" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-800">Borrador guardado</p>
                <p class="text-xs text-zinc-400 mt-0.5">
                    <span x-text="draftDate ? formatDate(draftDate) : ''"></span>
                </p>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="button" @click="restoreDraft()"
                class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition-colors text-white border-0"
                style="background-color: rgb(var(--pf-600))">
                Restaurar
            </button>
            <button type="button" @click="clearDraft()"
                class="flex-1 py-1.5 rounded-lg text-xs font-medium transition-colors bg-zinc-100 text-zinc-500 border border-zinc-200 hover:text-zinc-700 hover:bg-zinc-200">
                Descartar
            </button>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="mb-8">
        <h1 class="pf-form-heading">Solicitud de Equipamiento y Soluciones Eléctricas.

</h1>
        <p class="pf-form-description">
        Complete los datos de su proyecto, configure los tableros o salas eléctricas requeridas y adjunte la documentación técnica disponible para nuestro equipo de ingeniería.
        </p>
    </div>

    <div class="pf-card">
        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    </div>

    <p class="mt-4 text-sm text-zinc-500"><span class="text-red-500">*</span> Campos obligatorios.</p>

    <x-filament-actions::modals />
    @endif
</div>
