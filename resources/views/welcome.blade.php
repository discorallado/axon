<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Axon PMS') }}</title>
    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-[#070c18] text-slate-200 antialiased min-h-screen flex flex-col font-sans">

    {{-- ── NAV ── --}}
    <nav class="px-6 lg:px-10 py-4 flex items-center justify-between border-b border-white/[0.06] starting:opacity-0 opacity-100 transition-opacity duration-500">

        {{-- Logo --}}
        <a href="/" class="flex items-center no-underline">
                <img src="{{ asset('storage/images/logo_axon.png') }}" width="100px" alt="CS Energy">

            <!-- <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <rect x="1" y="1" width="6" height="6" rx="1.5" fill="white"/>
                    <rect x="9" y="1" width="6" height="6" rx="1.5" fill="white" opacity=".5"/>
                    <rect x="1" y="9" width="6" height="6" rx="1.5" fill="white" opacity=".5"/>
                    <rect x="9" y="9" width="6" height="6" rx="1.5" fill="white"/>
                </svg>
            </div>
            <span class="font-mono font-medium text-[15px] text-white tracking-tight">
                axon<span class="text-blue-500">.</span>pms
            </span> -->
        </a>

        {{-- Auth nav --}}
        @if (Route::has('login'))
            <div class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="px-4 py-1.5 rounded-md border border-white/10 text-slate-300 hover:border-white/25 hover:text-white transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="text-slate-400 hover:text-white transition-colors px-2">
                        Iniciar sesión
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="px-4 py-1.5 rounded-md bg-blue-600 hover:bg-blue-500 text-white transition-colors font-medium">
                            Registrarse
                        </a>
                    @endif
                @endauth
            </div>
        @endif

    </nav>

    {{-- ── HERO ── --}}
    <main class="flex-1 flex flex-col items-center justify-center text-center px-6 py-24
                 starting:opacity-0 opacity-100 transition-opacity duration-750 delay-200">

        {{-- Status pill --}}
        <div class="inline-flex items-center gap-2 border border-blue-900/60 bg-blue-950/40
                    rounded-full px-3.5 py-1.5 text-xs font-mono text-blue-400 mb-8">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
            En desarrollo activo
        </div>

        {{-- Heading --}}
        <h1 class="text-4xl lg:text-6xl font-semibold text-white leading-[1.1] tracking-[-0.04em] mb-5 max-w-2xl">
            Gestión de proyectos para
            <span class="text-blue-500"> infraestructura crítica</span>
        </h1>

        {{-- Subtitle --}}
        <p class="text-slate-400 text-lg leading-relaxed max-w-md mb-10">
            PMIS para ingeniería eléctrica y construcción.<br>
            FAT, comisionamiento y trazabilidad en una sola plataforma.
        </p>

        {{-- CTAs --}}
        <div class="flex flex-wrap items-center justify-center gap-3">
            @auth
                <a href="{{ url('/dashboard') }}"
                   class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium transition-colors">
                    Ir al dashboard
                </a>
            @else
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium transition-colors">
                        Solicitar acceso
                    </a>
                @endif
                <a href="mailto:contacto@axonpms.dev"
                   class="px-6 py-2.5 rounded-lg border border-white/10 hover:border-white/25 text-slate-300 hover:text-white text-sm font-medium transition-colors">
                    Contactar
                </a>
            @endauth
        </div>

    </main>

    {{-- ── FOOTER ── --}}
    <footer class="px-6 lg:px-10 py-5 border-t border-white/[0.06] flex flex-wrap items-center justify-between gap-4
                   starting:opacity-0 opacity-100 transition-opacity duration-500 delay-300">
        <span class="font-mono text-xs text-slate-700">
            axon.pms &copy; {{ date('Y') }} — CSEnergy
        </span>
        <div class="flex gap-5 text-xs text-slate-700">
            <a href="mailto:contacto@axonpms.dev" class="hover:text-slate-400 transition-colors">Contacto</a>
            <a href="https://github.com" target="_blank" class="hover:text-slate-400 transition-colors">GitHub</a>
        </div>
    </footer>

</body>
</html>