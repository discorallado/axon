@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{
        state: $wire.$entangle('{{ $statePath }}'),
        signatureCanvas: null,
        isDrawing: false,
        hasSignature: false,
        signerName: '{{ $getSignerName() ?? '' }}',
        signerTitle: '{{ $getSignerTitle() ?? '' }}',
        signatureImage: null,
        
        init() {
            const canvas = this.$refs.canvas;
            if (canvas) {
                this.signatureCanvas = canvas.getContext('2d');
                this.setupCanvas();
                this.setupEvents();
            }
            
            // Cargar firma existente si hay
            if (this.state) {
                this.loadExistingSignature(this.state);
            }
        },
        
        setupCanvas() {
            const canvas = this.$refs.canvas;
            const container = this.$refs.container;
            canvas.width = container.offsetWidth;
            canvas.height = 200;
            
            this.signatureCanvas.strokeStyle = '#000000';
            this.signatureCanvas.lineWidth = 2;
            this.signatureCanvas.lineCap = 'round';
            this.signatureCanvas.lineJoin = 'round';
        },
        
        setupEvents() {
            const canvas = this.$refs.canvas;
            
            // Eventos para mouse
            canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
            canvas.addEventListener('mousemove', (e) => this.draw(e));
            canvas.addEventListener('mouseup', () => this.stopDrawing());
            canvas.addEventListener('mouseout', () => this.stopDrawing());
            
            // Eventos para touch
            canvas.addEventListener('touchstart', (e) => this.startDrawing(e));
            canvas.addEventListener('touchmove', (e) => this.draw(e));
            canvas.addEventListener('touchend', () => this.stopDrawing());
        },
        
        getCoordinates(e) {
            const canvas = this.$refs.canvas;
            const rect = canvas.getBoundingClientRect();
            
            if (e.touches && e.touches.length > 0) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            }
            
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        },
        
        startDrawing(e) {
            e.preventDefault();
            this.isDrawing = true;
            const coords = this.getCoordinates(e);
            this.signatureCanvas.beginPath();
            this.signatureCanvas.moveTo(coords.x, coords.y);
        },
        
        draw(e) {
            if (!this.isDrawing) return;
            e.preventDefault();
            
            const coords = this.getCoordinates(e);
            this.signatureCanvas.lineTo(coords.x, coords.y);
            this.signatureCanvas.stroke();
        },
        
        stopDrawing() {
            if (this.isDrawing) {
                this.isDrawing = false;
                this.hasSignature = true;
                this.saveSignature();
            }
        },
        
        saveSignature() {
            const canvas = this.$refs.canvas;
            this.signatureImage = canvas.toDataURL('image/png');
            this.state = this.signatureImage;
        },
        
        loadExistingSignature(dataUrl) {
            const img = new Image();
            img.onload = () => {
                this.signatureCanvas.drawImage(img, 0, 0);
                this.hasSignature = true;
                this.signatureImage = dataUrl;
            };
            img.src = dataUrl;
        },
        
        clear() {
            const canvas = this.$refs.canvas;
            this.signatureCanvas.clearRect(0, 0, canvas.width, canvas.height);
            this.hasSignature = false;
            this.signatureImage = null;
            this.state = null;
        },
        
        downloadSignature() {
            if (!this.hasSignature) return;
            
            const link = document.createElement('a');
            link.download = 'firma_' + Date.now() + '.png';
            link.href = this.signatureImage;
            link.click();
        }
    }" 
    x-init="init()"
    class="space-y-3"
    >
        {{-- Información del firmante --}}
        @if($getSignerName() || $getSignerTitle())
            <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                @if($getSignerName())
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $getSignerName() }}</p>
                @endif
                @if($getSignerTitle())
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $getSignerTitle() }}</p>
                @endif
            </div>
        @endif

        {{-- Canvas de firma --}}
        <div x-ref="container" class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 overflow-hidden">
            <canvas 
                x-ref="canvas"
                class="w-full cursor-crosshair touch-none"
                style="height: 200px;"
            ></canvas>
            
            {{-- Placeholder cuando no hay firma --}}
            <div 
                x-show="!hasSignature"
                class="absolute inset-0 flex items-center justify-center pointer-events-none"
            >
                <p class="text-gray-400 dark:text-gray-500 text-sm">Firme aquí con el mouse o dedo</p>
            </div>
        </div>

        {{-- Botones de acción --}}
        <div class="flex gap-2">
            <x-filament::button
                type="button"
                color="danger"
                size="sm"
                x-show="hasSignature"
                x-on:click="clear()"
            >
                <x-heroicon-s-trash class="w-4 h-4 mr-1" />
                Limpiar
            </x-filament::button>
            
            <x-filament::button
                type="button"
                color="gray"
                size="sm"
                x-show="hasSignature"
                x-on:click="downloadSignature()"
            >
                <x-heroicon-s-download class="w-4 h-4 mr-1" />
                Descargar
            </x-filament::button>

            @if($isExternalSignatureAllowed())
                <x-filament::button
                    tag="a"
                    href="#"
                    color="primary"
                    size="sm"
                >
                    <x-heroicon-s-document-arrow-up class="w-4 h-4 mr-1" />
                    Subir Firma Escaneada
                </x-filament::button>
            @endif
        </div>

        {{-- Input oculto para almacenamiento --}}
        <input 
            type="hidden" 
            {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
            x-model="state"
        />
    </div>
</x-dynamic-component>
