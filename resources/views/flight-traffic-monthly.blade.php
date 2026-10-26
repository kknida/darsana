<x-app-layout>
    <div class="dashboard-screen-container px-3 sm:px-6 lg:px-8 pb-12 space-y-4 sm:space-y-6">

        <!-- ======================================================== -->
        <!-- TOP HEADER & SUB-NAV                                     -->
        <!-- ======================================================== -->
        <x-flight-traffic-header activeTab="monthly" />

        <!-- ======================================================== -->
        <!-- FILTER BAR BULANAN                                       -->
        <!-- ======================================================== -->
        <div class="bg-white rounded-2xl p-3.5 sm:p-4 shadow-sm border border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
                <div class="w-9 sm:w-10 h-9 sm:h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-slate-800 tracking-tight">Data Performa Bulanan</h2>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium">Rekapitulasi traffic penerbangan bulan <span class="font-bold text-blue-600">{{ $selectedMonthName }}</span></p>
                </div>
            </div>

            <!-- Filter Controls: Dropdown Data Setiap Bulan -->
            <form method="GET" action="{{ route('flight-traffic-info.monthly') }}" class="flex items-center gap-2 m-0 w-full sm:w-auto justify-end">
                <label for="month" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Bulan:</label>
                <select name="month" id="month" onchange="this.form.submit()" 
                        class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 sm:px-3.5 py-1.5 sm:py-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer shadow-sm">
                    @foreach($monthOptions as $val => $name)
                        <option value="{{ $val }}" {{ $selectedMonth === $val ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>

                <button type="button" class="flex items-center gap-1.5 px-3 py-1.5 sm:py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 sm:h-4 w-3.5 sm:w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                </button>
            </form>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 1: 4 MONTHLY KPI CARDS (Bulan Terpilih)               -->
        <!-- ======================================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Card 1: Total Flight Bulan Terpilih -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-100/80 text-blue-600 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Flight</p>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($monthlyKpi['total_flight']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold pl-1 {{ str_starts_with($monthlyKpi['total_flight']['growth'], '+') ? 'text-emerald-600' : 'text-rose-500' }}">
                        <span>{{ str_starts_with($monthlyKpi['total_flight']['growth'], '+') ? '▲' : '▼' }}</span>
                        <span>{{ $monthlyKpi['total_flight']['growth'] }}</span>
                        <span class="text-slate-400 font-normal">({{ $monthlyKpi['total_flight']['subtext'] }})</span>
                    </div>
                </div>
                <!-- Mini Bar Sparkline -->
                <div class="flex items-end gap-1 h-12 pl-2">
                    @foreach($monthlyKpi['total_flight']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-blue-600 to-sky-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 2: Flight Domestik -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-sky-100/80 text-sky-600 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Flight Domestik</p>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($monthlyKpi['domestik']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-blue-600 pl-1">
                        <span>●</span>
                        <span>{{ $monthlyKpi['domestik']['growth'] }}</span>
                        <span class="text-slate-400 font-normal">dari total flight</span>
                    </div>
                </div>
                <div class="flex items-end gap-1 h-12 pl-2">
                    @foreach($monthlyKpi['domestik']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-sky-600 to-cyan-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 3: Flight Internasional -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100/80 text-indigo-600 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Flight Internasional</p>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($monthlyKpi['internasional']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-amber-500 pl-1">
                        <span>●</span>
                        <span>{{ $monthlyKpi['internasional']['growth'] }}</span>
                        <span class="text-slate-400 font-normal">dari total flight</span>
                    </div>
                </div>
                <div class="flex items-end gap-1 h-12 pl-2">
                    @foreach($monthlyKpi['internasional']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-indigo-600 to-blue-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 4: Pendapatan Bulan Ini -->
            <div class="bg-gradient-to-br from-amber-50/70 via-white to-amber-50/40 rounded-2xl p-5 shadow-sm border border-amber-200/60 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-amber-800 uppercase tracking-wider">Pendapatan Bulan Ini</p>
                            <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Rp {{ number_format($monthlyKpi['revenue']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 pl-1">
                        <span>★</span>
                        <span>{{ $monthlyKpi['revenue']['growth'] }}</span>
                        <span class="text-slate-400 font-normal">({{ $monthlyKpi['revenue']['subtext'] }})</span>
                    </div>
                </div>
                <div class="flex items-end gap-1 h-12 pl-2">
                    @foreach($monthlyKpi['revenue']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-amber-500 to-yellow-300 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 2: COMPARATIVE CHART BULANAN                         -->
        <!-- ======================================================== -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 gap-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-800 tracking-tight">Grafik Kinerja 12 Bulan: Aktual 2026 vs Target vs 2025</h3>
                        <p class="text-xs text-slate-400">Menampilkan tren bulanan sepanjang tahun 2026</p>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm bg-blue-600"></span>
                        <span>Aktual 2026</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm bg-emerald-500"></span>
                        <span>Target 2026</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-1 bg-slate-400"></span>
                        <span>Realisasi 2025</span>
                    </div>
                </div>
            </div>

            <div class="relative w-full h-80">
                <canvas id="monthlyComparisonChart"></canvas>
            </div>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 3: TABEL REKAPITULASI TRAFFIC BULANAN                -->
        <!-- ======================================================== -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-800 tracking-tight">Tabel Rekapitulasi Data Setiap Bulan (Tahun 2026)</h3>
                        <p class="text-xs text-slate-400">Bulan yang sedang aktif ditandai dengan sorotan biru</p>
                    </div>
                </div>
                <span class="text-xs font-semibold text-slate-400">Mata Uang: IDR (Rupiah)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-200 text-[11px] uppercase tracking-wider bg-slate-50/70">
                            <th class="py-3 px-4 font-bold">Bulan</th>
                            <th class="py-3 px-4 font-bold">Flight Domestik</th>
                            <th class="py-3 px-4 font-bold">Flight Internasional</th>
                            <th class="py-3 px-4 font-bold">Total Flight</th>
                            <th class="py-3 px-4 font-bold text-center">MoM Growth</th>
                            <th class="py-3 px-4 font-bold text-right">Pendapatan (IDR)</th>
                            <th class="py-3 px-4 font-bold text-right">% Target</th>
                            <th class="py-3 px-4 font-bold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($monthlyTable as $row)
                            @php
                                $isCurrent = ($row['code'] === $selectedMonth);
                            @endphp
                            <tr class="transition-colors {{ $isCurrent ? 'bg-blue-50/60 font-bold border-l-4 border-blue-600' : 'hover:bg-slate-50' }}">
                                <td class="py-3.5 px-4 text-slate-800 flex items-center gap-2">
                                    <span>{{ $row['month'] }}</span>
                                    @if($isCurrent)
                                        <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded-md bg-blue-600 text-white shadow-xs">Aktif</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">{{ number_format($row['domestik'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-slate-600">{{ number_format($row['internasional'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 font-black text-slate-900">{{ number_format($row['total'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    @if(str_starts_with($row['growth'], '+'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600">
                                            {{ $row['growth'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-600">
                                            {{ $row['growth'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-black text-slate-800">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4 text-right">
                                    <span class="font-bold {{ $row['achievement'] >= 100 ? 'text-emerald-600' : 'text-amber-600' }}">
                                        {{ number_format($row['achievement'], 1, ',', '.') }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if(!$isCurrent)
                                        <a href="{{ route('flight-traffic-info.monthly', ['month' => $row['code']]) }}" 
                                           class="text-[11px] font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                            Pilih
                                        </a>
                                    @else
                                        <span class="text-[11px] text-slate-400 font-normal">Dipilih</span>
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
            const ctxMonthly = document.getElementById('monthlyComparisonChart');
            if (ctxMonthly) {
                const labels = @json($monthlyComparison['labels']);
                const actual = @json($monthlyComparison['actual_2026']);
                const target = @json($monthlyComparison['target_2026']);
                const lastYear = @json($monthlyComparison['last_year_2025']);

                new Chart(ctxMonthly, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                type: 'bar',
                                label: 'Aktual 2026',
                                data: actual,
                                backgroundColor: '#2563eb',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7,
                                order: 2
                            },
                            {
                                type: 'bar',
                                label: 'Target 2026',
                                data: target,
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7,
                                order: 3
                            },
                            {
                                type: 'line',
                                label: 'Realisasi 2025',
                                data: lastYear,
                                borderColor: '#94a3b8',
                                borderWidth: 2,
                                borderDash: [4, 4],
                                pointBackgroundColor: '#94a3b8',
                                pointRadius: 3,
                                fill: false,
                                order: 1
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
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ${new Intl.NumberFormat('id-ID').format(context.raw)} flight`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 11, weight: '600' }, color: '#64748b' }
                            },
                            y: {
                                ticks: {
                                    callback: function(v) { return new Intl.NumberFormat('id-ID').format(v); },
                                    font: { size: 11 },
                                    color: '#94a3b8'
                                },
                                grid: { color: '#f1f5f9' },
                                title: {
                                    display: true,
                                    text: 'Jumlah Flight',
                                    color: '#94a3b8',
                                    font: { size: 11, weight: 'bold' }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
