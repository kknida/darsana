<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>DARSANA &mdash; AirNav</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/airnav-logo.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @elseif (config('app.debug'))
            <div style="background:#b91c1c;color:#fff;padding:12px;font-family:sans-serif;font-size:14px">
                ⚠️ Vite manifest tidak ditemukan. Jalankan <code>npm run build</code> lalu commit folder <code>public/build/</code>.
            </div>
        @endif
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
        <script>
            // Segera pasang class tv-mode-active sebelum DOM render jika user sebelumnya dalam Mode TV
            if (localStorage.getItem('darsana_tv_mode') === 'true') {
                document.documentElement.classList.add('tv-mode-active');
                window.addEventListener('DOMContentLoaded', function() {
                    document.body.classList.add('tv-mode-active');
                });
            }
        </script>
        @livewireStyles
    </head>
    <body class="bg-slate-50 font-sans antialiased text-slate-900">
        <div class="min-h-screen w-full" id="app-root-container">
            <!-- Navigation dengan padding (disembunyikan saat Mode TV Fullscreen aktif) -->
            <div id="main-navigation-bar" class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6 transition-all duration-300">
                @include('layouts.navigation')
            </div>

            <main class="w-full">
                {{ $slot }}
                
                @livewireScripts
            </main>
        </div>
    </body>
</html>