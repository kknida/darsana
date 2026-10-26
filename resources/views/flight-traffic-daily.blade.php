<x-app-layout>
    <div class="dashboard-screen-container px-3 sm:px-6 lg:px-8 pb-12 space-y-4 sm:space-y-6">

        <!-- ======================================================== -->
        <!-- TOP HEADER & SUB-NAV                                     -->
        <!-- ======================================================== -->
        <x-flight-traffic-header activeTab="daily" />

        <!-- ======================================================== -->
        <!-- FILTER BAR HARIAN                                        -->
        <!-- ======================================================== -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-4 shadow-sm border border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
                <div class="w-9 sm:w-10 h-9 sm:h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-slate-800 tracking-tight">Monitoring Traffic Harian</h2>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium">Distribusi per jam & pergerakan harian tanggal {{ date('d F Y', strtotime($selectedDate)) }}</p>
                </div>
            </div>

            <!-- Date Filter & Quick Picks -->
            <form method="GET" action="{{ route('flight-traffic-info.daily') }}" class="flex flex-wrap items-center gap-2 m-0 w-full sm:w-auto justify-end">
                <label for="date" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal:</label>
                <input type="date" name="date" id="date" value="{{ $selectedDate }}" onchange="this.form.submit()"
                       class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-2.5 sm:px-3 py-1.5 sm:py-2 focus:ring-blue-500 focus:border-blue-500">

                <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 sm:py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 sm:h-4 w-3.5 sm:w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Log
                </button>
            </form>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 1: 4 DAILY KPI CARDS                                 -->
        <!-- ======================================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Card 1: Total Flight Hari Ini -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-100/80 text-blue-600 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Flight Hari Ini</p>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($dailyKpi['today_flight']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 pl-1">
                        <span>▲</span>
                        <span>{{ $dailyKpi['today_flight']['growth'] }}</span>
                        <span class="text-slate-400 font-normal">{{ $dailyKpi['today_flight']['vs_period'] }}</span>
                    </div>
                </div>
                <!-- Mini Bar Sparkline -->
                <div class="flex items-end gap-1 h-12 pl-2">
                    @foreach($dailyKpi['today_flight']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-blue-600 to-sky-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 2: Peak Hour -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100/80 text-indigo-600 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jam Tersibuk (Peak Hour)</p>
                            <h3 class="text-base font-black text-slate-900 tracking-tight">{{ $dailyKpi['peak_hour']['hour'] }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-600 pl-1">
                        <span>⚡</span>
                        <span>{{ $dailyKpi['peak_hour']['value'] }} {{ $dailyKpi['peak_hour']['unit'] }}</span>
                        <span class="text-slate-400 font-normal">({{ $dailyKpi['peak_hour']['subtext'] }})</span>
                    </div>
                </div>
                <div class="flex items-end gap-1 h-12 pl-2">
                    @foreach($dailyKpi['peak_hour']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-indigo-600 to-blue-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 3: Domestik vs Internasional Hari Ini -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2 w-full">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Komposisi Harian</p>
                        <span class="text-[11px] font-bold text-blue-600">{{ $dailyKpi['dom_vs_intl']['dom_percent'] }}% Dom</span>
                    </div>
                    
                    <div class="flex items-center justify-between pt-1">
                        <div>
                            <p class="text-xs text-slate-400 font-medium">Domestik</p>
                            <p class="text-lg font-black text-slate-800">{{ $dailyKpi['dom_vs_intl']['domestik'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-400 font-medium">Internasional</p>
                            <p class="text-lg font-black text-amber-600">{{ $dailyKpi['dom_vs_intl']['internasional'] }}</p>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-full h-2 bg-amber-200 rounded-full overflow-hidden flex">
                        <div class="bg-blue-600 h-full rounded-full" style="width: {{ $dailyKpi['dom_vs_intl']['dom_percent'] }}%;"></div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Daily Revenue -->
            <div class="bg-gradient-to-br from-amber-50/70 via-white to-amber-50/40 rounded-2xl p-5 shadow-sm border border-amber-200/60 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Pendapatan Hari Ini</p>
                            <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Rp {{ number_format($dailyKpi['daily_revenue']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 pl-1">
                        <span>▲</span>
                        <span>{{ $dailyKpi['daily_revenue']['growth'] }}</span>
                        <span class="text-slate-400 font-normal">({{ $dailyKpi['daily_revenue']['vs_period'] }})</span>
                    </div>
                </div>
                <div class="flex items-end gap-1 h-12 pl-2">
                    @foreach($dailyKpi['daily_revenue']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-amber-500 to-yellow-300 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 2: HOURLY TRAFFIC & 31-DAY TREND                     -->
        <!-- ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Hourly Traffic Curve (7 cols) -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 gap-3 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-800 tracking-tight">Distribusi Pergerakan per Jam (00:00 - 23:00 WIB)</h3>
                            <p class="text-xs text-slate-400">Komparasi pola Keberangkatan (Departure) dan Kedatangan (Arrival)</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs font-semibold text-slate-600">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            <span>Departure</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span>Arrival</span>
                        </div>
                    </div>
                </div>

                <div class="relative w-full h-72">
                    <canvas id="hourlyTrafficChart"></canvas>
                </div>
            </div>

            <!-- 31 Days Trend in Current Month (5 cols) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-800 tracking-tight">Tren Harian (Okt 2026)</h3>
                            <p class="text-xs text-slate-400">Total volume penerbangan harian</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">31 Hari</span>
                </div>

                <div class="relative w-full h-72">
                    <canvas id="dailyDaysTrendChart"></canvas>
                </div>
            </div>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 3: TABEL LOG TRAFFIC HARIAN                          -->
        <!-- ======================================================== -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-base font-black text-slate-800 tracking-tight">Log Riwayat Traffic Harian (7 Hari Terakhir)</h3>
                </div>
                <span class="text-xs font-semibold text-slate-400">Pembaruan otomatis tiap pergantian jam</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-200 text-[11px] uppercase tracking-wider bg-slate-50/70">
                            <th class="py-3 px-4 font-bold">Tanggal</th>
                            <th class="py-3 px-4 font-bold">Hari</th>
                            <th class="py-3 px-4 font-bold">Domestik</th>
                            <th class="py-3 px-4 font-bold">Internasional</th>
                            <th class="py-3 px-4 font-bold">Total Flight</th>
                            <th class="py-3 px-4 font-bold">Jam Tersibuk</th>
                            <th class="py-3 px-4 font-bold text-right">Pendapatan Harian</th>
                            <th class="py-3 px-4 font-bold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($dailyLogTable as $row)
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-800">{{ $row['date'] }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-600">{{ $row['day'] }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-600">{{ number_format($row['domestik'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-600">{{ number_format($row['internasional'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 font-black text-slate-900">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 font-semibold text-indigo-600">{{ $row['peak_time'] }}</td>
                                <td class="py-3.5 px-4 text-right font-black text-slate-800">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($row['status'] === 'Peak High')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-600 border border-amber-200">
                                            Peak High
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                            Normal
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- STYLESHEET MODE TV (BERSIH, ELEGAN, DAN TIDAK TERPOTONG)   -->
    <!-- ======================================================== -->
    <style>
        /* MODE TV FULLSCREEN (1 LAYAR PENUH TIDAK SCROLL DI LAYAR 1080P/4K) */
        body.tv-mode-active {
            background-color: #f8fafc !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow-x: hidden !important;
        }

        body.tv-mode-active #main-navigation-bar {
            display: none !important;
        }

        body.tv-mode-active #standard-header-banner {
            display: none !important;
        }

        body.tv-mode-active #tv-broadcast-bar {
            display: flex !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 50 !important;
        }

        /* Di layar TV / Desktop Fullscreen, hilangkan padding berlebih */
        body.tv-mode-active .dashboard-screen-container {
            padding: 8px 16px 12px 16px !important;
            margin: 0 !important;
        }

        /* Skala pas dan rapi saat TV Mode aktif pada layar besar */
        @media (min-height: 800px) {
            body.tv-mode-active {
                height: 100vh !important;
                overflow: hidden !important;
            }
            body.tv-mode-active .dashboard-screen-container {
                height: calc(100vh - 46px) !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                gap: 8px !important;
                padding-bottom: 8px !important;
                overflow-y: auto !important;
            }
            body.tv-mode-active .dashboard-screen-container > * + * {
                margin-top: 0 !important;
            }
        }
    </style>

    <!-- Charts Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Hourly Chart
            const ctxHourly = document.getElementById('hourlyTrafficChart');
            if (ctxHourly) {
                const labels = @json($hourlyTraffic['labels']);
                const dep = @json($hourlyTraffic['departure']);
                const arr = @json($hourlyTraffic['arrival']);

                new Chart(ctxHourly, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Departure',
                                data: dep,
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2,
                                pointRadius: 2.5
                            },
                            {
                                label: 'Arrival',
                                data: arr,
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2,
                                pointRadius: 2.5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 12 },
                                padding: 10,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: '600' }, color: '#64748b', maxRotation: 45 }
                            },
                            y: {
                                ticks: { font: { size: 11 }, color: '#94a3b8' },
                                grid: { color: '#f1f5f9' },
                                title: {
                                    display: true,
                                    text: 'Pergerakan / Jam',
                                    color: '#94a3b8',
                                    font: { size: 11, weight: 'bold' }
                                }
                            }
                        }
                    }
                });
            }

            // 2. 31 Days Trend Chart
            const ctxDailyDays = document.getElementById('dailyDaysTrendChart');
            if (ctxDailyDays) {
                const dayLabels = @json($dailyTrendDays);
                const dayFlights = @json($dailyTrendFlights);

                new Chart(ctxDailyDays, {
                    type: 'bar',
                    data: {
                        labels: dayLabels,
                        datasets: [{
                            label: 'Jumlah Flight',
                            data: dayFlights,
                            backgroundColor: '#0070f3',
                            borderRadius: 3,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 12 },
                                padding: 10,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    font: { size: 9 },
                                    color: '#64748b',
                                    maxTicksLimit: 10
                                }
                            },
                            y: {
                                beginAtZero: true,
                                suggestedMax: 300,
                                ticks: { font: { size: 11 }, color: '#94a3b8' },
                                grid: { color: '#f1f5f9' }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>

