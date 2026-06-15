<!DOCTYPE html>
<html
    lang="es"
    dir="ltr"
    class="fi dark antialiased"
>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    <style>
        [x-cloak=''], [x-cloak='x-cloak'], [x-cloak='1'] { display: none !important; }
    </style>

    @filamentStyles

    {{-- CSS del tema de Filament (variables Tailwind + colores del panel) --}}
    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontHtml() }}

    <style>
        :root {
            --font-family: '{{ filament()->getFontFamily() }}';
            --default-theme-mode: dark;

            {{-- Variables de color primario para los pf-* helpers --}}
            @foreach(config('public-form.primary') as $shade => $rgb)
            --pf-{{ $shade }}: {{ $rgb }};
            @endforeach
        }
    </style>

    @livewireStyles
    @vite(['resources/css/app.css'])
</head>
<body class="pf-body">

    <header class="pf-header">
        <div class="pf-header-inner">
            <div class="flex">
                <img src="{{ asset('storage/images/logo_cse.png') }}" width="200px" alt="CS Energy">
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
