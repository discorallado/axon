<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">

    <header class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="max-w-2xl mx-auto">
            <span class="text-lg font-semibold text-gray-800">{{ config('app.name') }}</span>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-10">
        @yield('content')
    </main>

    <footer class="max-w-2xl mx-auto px-4 py-6 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </footer>

</body>
</html>
