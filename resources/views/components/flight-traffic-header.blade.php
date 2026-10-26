@props([
    'activeTab' => 'summary',
    'lastUpdate' => null
])

@php
    if (!$lastUpdate) {
        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Ambil tanggal terbaru dari database (traffic_data atau traffic_upload)
        $latestRecordDate = \Illuminate\Support\Facades\DB::table('traffic_data')
            ->whereNotNull('tanggal')
            ->max('tanggal');

        if ($latestRecordDate) {
            $cDate = \Carbon\Carbon::parse($latestRecordDate);
            $m = (int) $cDate->format('m');
            $lastUpdate = $cDate->format('d') . ' ' . ($indonesianMonths[$m] ?? $cDate->format('M')) . ' ' . $cDate->format('Y');
        } else {
            $lastUpdate = date('d M Y');
        }
    }
@endphp

<!-- ================================================================= -->
<!-- 1. BROADCAST TICKER BAR (Hanya Tampil Saat Mode TV Fullscreen)     -->
<!-- ================================================================= -->
<div id="tv-broadcast-bar" class="hidden items-center justify-between px-3 sm:px-5 py-1.5 bg-slate-900 text-white border-b border-slate-800 shadow-xl z-50">
    <!-- Left: AirNav DARSANA Brand & Title -->
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="font-black text-sm tracking-wider text-white">DARSANA</span>
            <!-- <span class="text-[9px] uppercase font-black tracking-widest bg-blue-600 px-2 py-0.5 rounded text-white shadow-xs">FIDS TV</span> -->
        </div>
        <span class="text-slate-600 hidden sm:inline">|</span>
        <div class="hidden sm:flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-300">
                <!-- @if($activeTab === 'summary')
                    Flight Traffic Info &mdash; Overview
                @elseif($activeTab === 'monthly')
                    Flight Traffic Info &mdash; Bulanan
                @else
                    Flight Traffic Info &mdash; Harian Realtime
                @endif -->
                @if($activeTab === 'summary')
                    Overview
                @elseif($activeTab === 'monthly')
                    Bulanan
                @else
                    Harian Realtime
                @endif
            </span>
            <span class="text-[10px] bg-slate-800 text-slate-300 border border-slate-700 px-2 py-0.5 rounded font-mono font-medium">Update: {{ $lastUpdate }}</span>
            <!-- <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Live</span> -->
        </div>
    </div>

    <!-- Center: Tabs Switcher in TV Mode -->
    <div class="flex items-center gap-1 bg-slate-800/90 p-1 rounded-xl border border-slate-700/60 shadow-inner">
        <a href="{{ route('flight-traffic-info') }}" 
           onclick="localStorage.setItem('darsana_tv_mode', 'true');"
           class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $activeTab === 'summary' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
            Summary
        </a>
        <a href="{{ route('flight-traffic-info.monthly') }}" 
           onclick="localStorage.setItem('darsana_tv_mode', 'true');"
           class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $activeTab === 'monthly' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">
            Bulanan
        </a>
        <a href="{{ route('flight-traffic-info.daily') }}" 
           onclick="localStorage.setItem('darsana_tv_mode', 'true');"
           class="px-3 py-1 rounded-lg text-xs font-bold transition-all {{ $activeTab === 'daily' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">
            Harian
        </a>
    </div>

    <!-- Right: Real-time Digital Clock & Exit Button -->
    <div class="flex items-center gap-3 sm:gap-4">
        <div class="text-right">
            <div id="tv-live-clock" class="text-xs sm:text-sm font-mono font-black text-amber-400 tracking-wider">00:00:00 WIB</div>
            <div id="tv-live-date" class="hidden sm:block text-[9px] text-slate-400 font-medium tracking-tight">Memuat tanggal...</div>
        </div>

        <!-- Exit Fullscreen TV Button -->
        <button type="button" 
                onclick="exitTvMode()" 
                title="Keluar dari Tampilan TV Fullscreen (Tombol Esc)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold shadow-md shadow-red-900/30 transition-all active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="hidden sm:inline">Keluar</span>
            <span class="text-[9px] bg-red-700/80 px-1 py-0.5 rounded font-mono font-bold">Esc</span>
        </button>
    </div>
</div>


<!-- ================================================================= -->
<!-- 2. STANDARD HERO BANNER (Tampil Saat Mode Web Normal)              -->
<!-- ================================================================= -->
<div id="standard-header-banner" class="relative overflow-hidden rounded-2xl bg-slate-900 shadow-lg border border-slate-200/50 transition-all duration-300">
    <!-- Background Airport Image with Gradient Overlay -->
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1542296332-2e4473faf563?auto=format&fit=crop&w=1600&q=80" 
             alt="Airport Tarmac" 
             class="w-full h-full object-cover object-center opacity-40 mix-blend-luminosity scale-105 transition-transform duration-700">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 via-slate-900/80 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
    </div>

    <!-- Header Content: Responsive HP & Desktop -->
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between p-3.5 sm:p-5 gap-3.5 sm:gap-4">
        <!-- Navigation Tabs (Summary, Bulanan, Harian) - Responsive Scroll di HP -->
        <div class="flex items-center gap-1.5 sm:gap-2 bg-white/90 backdrop-blur-md p-1 sm:p-1.5 rounded-2xl shadow-sm border border-white/50 w-full md:w-fit overflow-x-auto">
            
            <!-- Summary Tab -->
            <a href="{{ route('flight-traffic-info') }}" 
               class="flex-shrink-0 flex items-center gap-2 px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm transition-all duration-200 {{ $activeTab === 'summary' ? 'font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-blue-500/20' : 'font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/70' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Summary
            </a>

            <!-- Bulanan Tab -->
            <a href="{{ route('flight-traffic-info.monthly') }}" 
               class="flex-shrink-0 flex items-center gap-2 px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm transition-all duration-200 {{ $activeTab === 'monthly' ? 'font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-blue-500/20' : 'font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/70' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $activeTab === 'monthly' ? 'text-white' : 'text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Bulanan
            </a>

            <!-- Harian Tab -->
            <a href="{{ route('flight-traffic-info.daily') }}" 
               class="flex-shrink-0 flex items-center gap-2 px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm transition-all duration-200 {{ $activeTab === 'daily' ? 'font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-blue-500/20' : 'font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/70' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $activeTab === 'daily' ? 'text-white' : 'text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Harian
            </a>
        </div>

        <!-- Right Side: Status Badge & TV FULLSCREEN BUTTON -->
        <div class="flex items-center justify-between md:justify-end gap-2.5 sm:gap-3 text-white">
            <div class="hidden lg:flex items-center gap-2 bg-black/35 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/10">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <!-- <span class="text-xs font-semibold uppercase tracking-wider text-slate-200">Flight Traffic Info</span> -->
                <!-- <span class="text-xs text-slate-400">|</span> -->
                <span class="text-xs text-slate-300 font-medium flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-blue-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Update: <span class="font-bold text-amber-300">{{ $lastUpdate }}</span></span>
                </span>
                <span class="text-xs text-slate-400">|</span>
                <span class="text-xs font-medium text-slate-300">
                    @if($activeTab === 'summary')
                        Ringkasan Eksekutif
                    @elseif($activeTab === 'monthly')
                        Analisis Bulanan
                    @else
                        Monitoring Harian
                    @endif
                </span>
            </div>

            <!-- Tombol Tampil di TV Fullscreen -->
            <button type="button" 
                    onclick="enterTvMode()" 
                    title="Tampilkan Full 1 Layar Penuh di Layar TV / Information Board"
                    class="flex items-center gap-2 px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-black bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-400 text-slate-950 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-[1.02] active:scale-95 transition-all cursor-pointer">
                <!-- Monitor TV Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Full Screen</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-800 hidden sm:inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- ================================================================= -->
<!-- 3. TV FULLSCREEN CONTROLLER SCRIPT & DIGITAL CLOCK                -->
<!-- ================================================================= -->
<script>
    // Live Clock Function
    function updateTvClock() {
        const clockEl = document.getElementById('tv-live-clock');
        const dateEl = document.getElementById('tv-live-date');
        if (!clockEl) return;

        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockEl.textContent = `${hours}:${minutes}:${seconds} WIB`;

        if (dateEl) {
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const dayName = days[now.getDay()];
            const dayDate = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            dateEl.textContent = `${dayName}, ${dayDate} ${monthName} ${year}`;
        }
    }

    // Update clock every second
    setInterval(updateTvClock, 1000);
    updateTvClock();

    // Check and restore TV Mode from localStorage on initial page load
    (function initTvModePersistence() {
        const isTvSaved = localStorage.getItem('darsana_tv_mode') === 'true';
        if (isTvSaved) {
            document.body.classList.add('tv-mode-active');
            
            // Re-request browser fullscreen on first user interaction if browser blocked auto-fullscreen
            const tryRequestFullscreen = function() {
                const elem = document.documentElement;
                if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                    if (elem.requestFullscreen) {
                        elem.requestFullscreen().catch(() => {});
                    } else if (elem.webkitRequestFullscreen) {
                        elem.webkitRequestFullscreen();
                    }
                }
                document.removeEventListener('click', tryRequestFullscreen);
            };
            document.addEventListener('click', tryRequestFullscreen);
        }
    })();

    // Enter TV Mode
    window.enterTvMode = function() {
        localStorage.setItem('darsana_tv_mode', 'true');
        document.body.classList.add('tv-mode-active');

        // Request browser native fullscreen
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen().catch(() => {});
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }

        // Trigger chart resize after DOM reflow
        setTimeout(resizeAllCharts, 150);
        setTimeout(resizeAllCharts, 400);
    };

    // Exit TV Mode
    window.exitTvMode = function() {
        localStorage.removeItem('darsana_tv_mode');
        document.body.classList.remove('tv-mode-active');

        if (document.exitFullscreen) {
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(() => {});
            }
        } else if (document.webkitExitFullscreen) {
            if (document.webkitFullscreenElement) {
                document.webkitExitFullscreen();
            }
        } else if (document.msExitFullscreen) {
            if (document.msFullscreenElement) {
                document.msExitFullscreen();
            }
        }

        setTimeout(resizeAllCharts, 150);
        setTimeout(resizeAllCharts, 400);
    };

    // Listen to native fullscreen exit (e.g. user presses Esc key on browser)
    document.addEventListener('fullscreenchange', function() {
        if (!document.fullscreenElement) {
            // Jika user keluar fullscreen secara manual, hapus state tv mode
            localStorage.removeItem('darsana_tv_mode');
            document.body.classList.remove('tv-mode-active');
            setTimeout(resizeAllCharts, 200);
        }
    });
    document.addEventListener('webkitfullscreenchange', function() {
        if (!document.webkitFullscreenElement) {
            localStorage.removeItem('darsana_tv_mode');
            document.body.classList.remove('tv-mode-active');
            setTimeout(resizeAllCharts, 200);
        }
    });

    // Also support ESC key directly
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.body.classList.contains('tv-mode-active')) {
            window.exitTvMode();
        }
    });

    // Helper to resize all registered Chart.js instances
    function resizeAllCharts() {
        if (typeof Chart !== 'undefined' && Chart.instances) {
            Object.values(Chart.instances).forEach(function(chart) {
                try {
                    chart.resize();
                } catch (err) {}
            });
        }
    }
</script>
