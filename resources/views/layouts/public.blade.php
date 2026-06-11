<!DOCTYPE html>
<html lang="es" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- CSS custom properties generadas desde config('public-form.primary') --}}
    <style>
        :root {
            @foreach(config('public-form.primary') as $shade => $rgb)
            --pf-{{ $shade }}: {{ $rgb }};
            @endforeach
        }
    </style>
</head>
<body class="pf-body">

    <header class="pf-header">
        <div class="pf-header-inner">
            <div class="flex items-center gap-2">
                <img src="{{ asset('storage/logo_cse.png') }}" width="250px" class="!w-10" alt="CS Energy">
            </div>
        </div>
    </header>

    <main class="pf-main">
        @yield('content')
    </main>

    <footer class="pf-footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </footer>

</body>
</html>
