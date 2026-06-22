<!DOCTYPE html>
<html lang="es" dir="ltr" x-data="{
    isDark: localStorage.getItem('pf_theme') === 'dark',
    toggle() {
        this.isDark = !this.isDark;
        localStorage.setItem('pf_theme', this.isDark ? 'dark' : 'light');
    }
}" x-bind:class="{ 'dark': isDark }" class="fi antialiased">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="/storage/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <style>
        [x-cloak=''],
        [x-cloak='x-cloak'],
        [x-cloak='1'] {
            display: none !important;
        }
    </style>

    @filamentStyles

    {{-- CSS --}}
    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}

    <style>
        :root {
            --font-family: '{{ filament()->getFontFamily() }}';
            --default-theme-mode: light;

            {{-- Variables de color primario para los pf-* helpers --}} @foreach (config('public-form.primary') as $shade => $rgb)
                --pf-{{ $shade }}: {{ $rgb }};
            @endforeach
        }
    </style>

    @livewireStyles
    @vite(['resources/css/app.css'])
</head>

<body class="pf-body">

    <!-- Añadimos la imagen de fondo con la directiva asset de Blade -->
    <header class="pf-header" style="background-image: url('{{ asset('storage/images/bg.png') }}');">

        <div class="absolute inset-0 bg-black/40 dark:bg-black/70 pointer-events-none"></div>

        <div class="pf-header-inner">

            <!-- Todo el contenido queda en esta única fila horizontal -->
            <div class="flex items-center justify-between w-full">

                <!-- Izquierda: Logo -->
                <div class="flex flex-shrink-0">
                    <img src="{{ asset('storage/images/logo_cse.png') }}" width="200px" alt="CS Energy"
                        class="brightness-0 invert dark:brightness-70 dark:invert-0">
                </div>

                <!-- Derecha: Texto y Botón juntos -->
                <div class="flex items-center gap-4 md:gap-6">

                    <!-- Texto solicitado (se oculta en pantallas muy chicas para que no choque con el logo) -->
                    <span
                        class="hidden sm:inline-block text-white font-bold tracking-wider text-sm md:text-base uppercase border-r border-white/20 pr-4 md:pr-6">
                        Soluciones Industriales
                    </span>

                    <!-- Tu Botón Dark -->
                    <button type="button" @click="toggle()"
                        :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        class="rounded-lg p-2 text-zinc-100 hover:bg-white/20 dark:text-zinc-200 dark:hover:bg-zinc-800/60 transition-colors"
                        aria-label="Cambiar tema">
                        {{-- Sol (visible en modo oscuro) --}}
                        <svg x-show="isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                        {{-- Luna (visible en modo claro) --}}
                        <svg x-show="!isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                    </button>
                </div>

            </div>

        </div>
    </header>




    <main class="pf-main">
        {{ $slot }}
    </main>

    <footer class="pf-footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </footer>

    @filamentScripts(withCore: true)
    @livewireScripts
</body>

</html>
