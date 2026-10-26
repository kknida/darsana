<x-app-layout>
    <div class="dashboard-screen-container px-3 sm:px-6 lg:px-8 pb-10 space-y-4 sm:space-y-5">

        <!-- ======================================================== -->
        <!-- TOP HEADER & SUB-NAV WITH TV FULLSCREEN BUTTON           -->
        <!-- ======================================================== -->
        <x-flight-traffic-header activeTab="summary" />


        <!-- ======================================================== -->
        <!-- ROW 1: 4 TOP KPI CARDS                                   -->
        <!-- ======================================================== -->
        <div class="tv-kpi-row grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Card 1: Total Flight -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-blue-100/80 text-blue-600 flex items-center justify-center shadow-inner flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 sm:h-6 w-5 sm:w-6 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Flight</p>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ number_format($kpi['total_flight']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 pl-1">
                        <span>▲</span>
                        <span>{{ $kpi['total_flight']['growth'] }}</span>
                        <span class="text-slate-400 font-normal text-[11px]">{{ $kpi['total_flight']['vs_period'] }}</span>
                    </div>
                </div>
                <!-- Mini Bar Sparkline -->
                <div class="flex items-end gap-1 h-10 sm:h-11 pl-2">
                    @foreach($kpi['total_flight']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-blue-600 to-sky-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 2: Domestik -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-sky-100/80 text-sky-600 flex items-center justify-center shadow-inner flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 sm:h-6 w-5 sm:w-6 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Domestik Flight</p>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ number_format($kpi['domestik']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 pl-1">
                        <span>▲</span>
                        <span>{{ $kpi['domestik']['growth'] }}</span>
                        <span class="text-slate-400 font-normal text-[11px]">{{ $kpi['domestik']['vs_period'] }}</span>
                    </div>
                </div>
                <!-- Mini Bar Sparkline -->
                <div class="flex items-end gap-1 h-10 sm:h-11 pl-2">
                    @foreach($kpi['domestik']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-sky-600 to-cyan-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 3: Internasional -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-indigo-100/80 text-indigo-600 flex items-center justify-center shadow-inner flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 sm:h-6 w-5 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Internasional Flight</p>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ number_format($kpi['internasional']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 pl-1">
                        <span>▲</span>
                        <span>{{ $kpi['internasional']['growth'] }}</span>
                        <span class="text-slate-400 font-normal text-[11px]">{{ $kpi['internasional']['vs_period'] }}</span>
                    </div>
                </div>
                <!-- Mini Bar Sparkline -->
                <div class="flex items-end gap-1 h-10 sm:h-11 pl-2">
                    @foreach($kpi['internasional']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-indigo-600 to-blue-400 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>

            <!-- Card 4: Potensi Pendapatan -->
            <div class="bg-gradient-to-br from-amber-50/70 via-white to-amber-50/40 rounded-2xl p-4 sm:p-4.5 shadow-sm border border-amber-200/60 hover:shadow-md transition-shadow relative overflow-hidden flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shadow-inner flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 sm:h-6 w-5 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">Estimasi Potensi Pendapatan</p>
                            <h3 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">Rp {{ number_format($kpi['potensi_pendapatan']['value'], 0, ',', '.') }}</h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 pl-1">
                        <span>▲</span>
                        <span>{{ $kpi['potensi_pendapatan']['growth'] }}</span>
                        <span class="text-slate-400 font-normal text-[11px]">{{ $kpi['potensi_pendapatan']['vs_period'] }}</span>
                    </div>
                </div>
                <!-- Mini Bar Sparkline Golden -->
                <div class="flex items-end gap-1 h-10 sm:h-11 pl-2">
                    @foreach($kpi['potensi_pendapatan']['sparkline'] as $barHeight)
                        <div class="w-1.5 bg-gradient-to-t from-amber-500 to-yellow-300 rounded-t-sm" style="height: {{ $barHeight }}%;"></div>
                    @endforeach
                </div>
            </div>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 2: TREN TRAFFIC & KOMPOSISI TRAFFIC                  -->
        <!-- ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 sm:gap-4">
            <!-- Left Chart: Tren Traffic (8 cols) -->
            <div class="lg:col-span-8 bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="flex flex-wrap items-center justify-between pb-2 gap-2">
                    <div class="flex items-center gap-2">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h2 class="text-sm sm:text-base font-black text-slate-800 tracking-tight">Tren Traffic</h2>
                    </div>

                    <!-- Chart Legend -->
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs font-semibold text-slate-600">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-blue-600"></span>
                            <span>Domestik</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-amber-500"></span>
                            <span>Internasional</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-0.5 bg-emerald-500 relative flex items-center justify-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 absolute"></span>
                            </span>
                            <span class="pl-1">Total Flight</span>
                        </div>
                    </div>
                </div>

                <!-- Canvas Chart Box -->
                <div class="relative w-full h-48 sm:h-56">
                    <canvas id="trafficTrendChart"></canvas>
                </div>
            </div>

            <!-- Right Chart: Komposisi Traffic (4 cols) -->
            <div class="lg:col-span-4 bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center gap-2 pb-2">
                    <div class="text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-slate-800 tracking-tight">Komposisi Traffic</h2>
                </div>

                <!-- Donut Chart & Side Details -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6 my-auto py-1">
                    <div class="relative w-36 h-36 sm:w-40 sm:h-40 flex-shrink-0 flex items-center justify-center">
                        <canvas id="trafficDonutChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                            <span class="text-lg sm:text-xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($trafficComposition['total'], 0, ',', '.') }}</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Total Flight</span>
                        </div>
                    </div>

                    <!-- Legend & Details -->
                    <div class="space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-blue-600 mt-1 flex-shrink-0"></span>
                            <div>
                                <p class="text-xs sm:text-sm font-bold text-slate-800 leading-tight">Domestik</p>
                                <p class="text-[11px] text-slate-500 font-medium">{{ number_format($trafficComposition['domestik']['flight'], 0, ',', '.') }} flight</p>
                                <p class="text-xs sm:text-sm font-black text-blue-600 leading-tight">{{ $trafficComposition['domestik']['percentage'] }}%</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-amber-500 mt-1 flex-shrink-0"></span>
                            <div>
                                <p class="text-xs sm:text-sm font-bold text-slate-800 leading-tight">Internasional</p>
                                <p class="text-[11px] text-slate-500 font-medium">{{ number_format($trafficComposition['internasional']['flight'], 0, ',', '.') }} flight</p>
                                <p class="text-xs sm:text-sm font-black text-amber-500 leading-tight">{{ $trafficComposition['internasional']['percentage'] }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-1.5 border-t border-slate-50 text-right">
                    <span class="text-[10px] font-semibold text-slate-400">Distribusi Domestik vs Internasional</span>
                </div>
            </div>
        </div>


        <!-- ======================================================== -->
        <!-- ======================================================== -->
        <!-- ROW 3: TOP 5 RANKINGS (SPLIT ATAS: DOMESTIK, BAWAH: INTERNASIONAL) -->
        <!-- ======================================================== -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
            <!-- 1. Top Airlines (by Flight) -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 shadow-sm border border-slate-100 flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-800 tracking-tight">Top Airlines <span class="text-[11px] font-semibold text-slate-400">(by Flight)</span></h3>
                    </div>
                </div>

                <!-- Bagian Atas: Domestik -->
                <div>
                    <div class="flex items-center justify-between pb-1.5 border-b border-blue-100">
                        <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            Domestik
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold">Top 5</span>
                    </div>
                    <table class="w-full text-xs mt-1">
                        <thead>
                            <tr class="text-slate-400 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-1 text-left w-4">#</th>
                                <th class="pb-1 text-left">Maskapai</th>
                                <th class="pb-1 text-right">Flight</th>
                                <th class="pb-1 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($topAirlinesFlightDom as $item)
                                <tr>
                                    <td class="py-1 font-bold text-slate-400">{{ $item['rank'] }}</td>
                                    <td class="py-1 font-semibold text-slate-700 pr-1 truncate max-w-[110px]" title="{{ $item['name'] }} ({{ $item['code'] }})">{{ $item['name'] }}</td>
                                    <td class="py-1 font-bold text-slate-900 text-right">{{ number_format($item['flight'], 0, ',', '.') }}</td>
                                    <td class="py-1 text-right">
                                        <div class="flex items-center justify-end gap-1 sm:gap-1.5">
                                            <div class="w-10 sm:w-12 h-2 bg-slate-100 rounded-sm overflow-hidden hidden sm:block">
                                                <div class="h-full bg-gradient-to-r {{ $item['color'] }} rounded-sm" style="width: {{ min(100, $item['share'] * 3) }}%;"></div>
                                            </div>
                                            <span class="font-bold text-slate-700 w-8 text-right text-[10px]">{{ number_format($item['share'], 1, ',', '.') }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Bagian Bawah: Internasional -->
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between pb-1.5 border-b border-amber-100">
                        <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Internasional
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold">Top 5</span>
                    </div>
                    <table class="w-full text-xs mt-1">
                        <thead>
                            <tr class="text-slate-400 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-1 text-left w-4">#</th>
                                <th class="pb-1 text-left">Maskapai</th>
                                <th class="pb-1 text-right">Flight</th>
                                <th class="pb-1 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($topAirlinesFlightIntl as $item)
                                <tr>
                                    <td class="py-1 font-bold text-slate-400">{{ $item['rank'] }}</td>
                                    <td class="py-1 font-semibold text-slate-700 pr-1 truncate max-w-[110px]" title="{{ $item['name'] }} ({{ $item['code'] }})">{{ $item['name'] }}</td>
                                    <td class="py-1 font-bold text-slate-900 text-right">{{ number_format($item['flight'], 0, ',', '.') }}</td>
                                    <td class="py-1 text-right">
                                        <div class="flex items-center justify-end gap-1 sm:gap-1.5">
                                            <div class="w-10 sm:w-12 h-2 bg-slate-100 rounded-sm overflow-hidden hidden sm:block">
                                                <div class="h-full bg-gradient-to-r {{ $item['color'] }} rounded-sm" style="width: {{ min(100, $item['share'] * 3) }}%;"></div>
                                            </div>
                                            <span class="font-bold text-slate-700 w-8 text-right text-[10px]">{{ number_format($item['share'], 1, ',', '.') }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Top Airlines (by Revenue) -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 shadow-sm border border-slate-100 flex flex-col justify-between space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-800 tracking-tight">Top Airlines <span class="text-[11px] font-semibold text-slate-400">(by Revenue)</span></h3>
                    </div>
                </div>

                <!-- Bagian Atas: Domestik -->
                <div>
                    <div class="flex items-center justify-between pb-1.5 border-b border-blue-100">
                        <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            Domestik
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold">Top 5</span>
                    </div>
                    <table class="w-full text-xs mt-1">
                        <thead>
                            <tr class="text-slate-400 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-1 text-left w-4">#</th>
                                <th class="pb-1 text-left">Maskapai</th>
                                <th class="pb-1 text-right">Pendapatan</th>
                                <th class="pb-1 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($topAirlinesRevDom as $item)
                                <tr>
                                    <td class="py-1 font-bold text-slate-400">{{ $item['rank'] }}</td>
                                    <td class="py-1 font-semibold text-slate-700 pr-1 truncate max-w-[100px]" title="{{ $item['name'] }} ({{ $item['code'] }})">{{ $item['name'] }}</td>
                                    <td class="py-1 font-bold text-slate-900 text-right truncate max-w-[95px]">Rp {{ number_format($item['revenue'], 0, ',', '.') }}</td>
                                    <td class="py-1 text-right">
                                        <div class="flex items-center justify-end gap-1 sm:gap-1.5">
                                            <div class="w-10 sm:w-12 h-2 bg-slate-100 rounded-sm overflow-hidden hidden sm:block">
                                                <div class="h-full bg-gradient-to-r {{ $item['color'] }} rounded-sm" style="width: {{ min(100, $item['share'] * 3) }}%;"></div>
                                            </div>
                                            <span class="font-bold text-slate-700 w-8 text-right text-[10px]">{{ number_format($item['share'], 1, ',', '.') }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Bagian Bawah: Internasional -->
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between pb-1.5 border-b border-amber-100">
                        <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Internasional
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold">Top 5</span>
                    </div>
                    <table class="w-full text-xs mt-1">
                        <thead>
                            <tr class="text-slate-400 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-1 text-left w-4">#</th>
                                <th class="pb-1 text-left">Maskapai</th>
                                <th class="pb-1 text-right">Pendapatan</th>
                                <th class="pb-1 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($topAirlinesRevIntl as $item)
                                <tr>
                                    <td class="py-1 font-bold text-slate-400">{{ $item['rank'] }}</td>
                                    <td class="py-1 font-semibold text-slate-700 pr-1 truncate max-w-[100px]" title="{{ $item['name'] }} ({{ $item['code'] }})">{{ $item['name'] }}</td>
                                    <td class="py-1 font-bold text-slate-900 text-right truncate max-w-[95px]">Rp {{ number_format($item['revenue'], 0, ',', '.') }}</td>
                                    <td class="py-1 text-right">
                                        <div class="flex items-center justify-end gap-1 sm:gap-1.5">
                                            <div class="w-10 sm:w-12 h-2 bg-slate-100 rounded-sm overflow-hidden hidden sm:block">
                                                <div class="h-full bg-gradient-to-r {{ $item['color'] }} rounded-sm" style="width: {{ min(100, $item['share'] * 2.5) }}%;"></div>
                                            </div>
                                            <span class="font-bold text-slate-700 w-8 text-right text-[10px]">{{ number_format($item['share'], 1, ',', '.') }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Top Route (by Flight) -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 shadow-sm border border-slate-100 flex flex-col justify-between space-y-3 md:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-800 tracking-tight">Top Route <span class="text-[11px] font-semibold text-slate-400">(by Flight)</span></h3>
                    </div>
                </div>

                <!-- Bagian Atas: Domestik -->
                <div>
                    <div class="flex items-center justify-between pb-1.5 border-b border-blue-100">
                        <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            Domestik
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold">Top 5</span>
                    </div>
                    <table class="w-full text-xs mt-1">
                        <thead>
                            <tr class="text-slate-400 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-1 text-left w-4">#</th>
                                <th class="pb-1 text-left">Rute</th>
                                <th class="pb-1 text-right">Flight</th>
                                <th class="pb-1 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($topRoutesDom as $item)
                                <tr>
                                    <td class="py-1 font-bold text-slate-400">{{ $item['rank'] }}</td>
                                    <td class="py-1 font-bold text-indigo-700 tracking-wide">{{ $item['route'] }}</td>
                                    <td class="py-1 font-bold text-slate-900 text-right">{{ number_format($item['flight'], 0, ',', '.') }}</td>
                                    <td class="py-1 text-right">
                                        <div class="flex items-center justify-end gap-1 sm:gap-1.5">
                                            <div class="w-10 sm:w-12 h-2 bg-slate-100 rounded-sm overflow-hidden hidden sm:block">
                                                <div class="h-full bg-gradient-to-r {{ $item['color'] }} rounded-sm" style="width: {{ min(100, $item['share'] * 5) }}%;"></div>
                                            </div>
                                            <span class="font-bold text-slate-700 w-8 text-right text-[10px]">{{ number_format($item['share'], 1, ',', '.') }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Bagian Bawah: Internasional -->
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between pb-1.5 border-b border-amber-100">
                        <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Internasional
                        </span>
                        <span class="text-[10px] text-slate-400 font-semibold">Top 5</span>
                    </div>
                    <table class="w-full text-xs mt-1">
                        <thead>
                            <tr class="text-slate-400 text-[10px] uppercase tracking-wider border-b border-slate-100">
                                <th class="pb-1 text-left w-4">#</th>
                                <th class="pb-1 text-left">Rute</th>
                                <th class="pb-1 text-right">Flight</th>
                                <th class="pb-1 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($topRoutesIntl as $item)
                                <tr>
                                    <td class="py-1 font-bold text-slate-400">{{ $item['rank'] }}</td>
                                    <td class="py-1 font-bold text-amber-700 tracking-wide">{{ $item['route'] }}</td>
                                    <td class="py-1 font-bold text-slate-900 text-right">{{ number_format($item['flight'], 0, ',', '.') }}</td>
                                    <td class="py-1 text-right">
                                        <div class="flex items-center justify-end gap-1 sm:gap-1.5">
                                            <div class="w-10 sm:w-12 h-2 bg-slate-100 rounded-sm overflow-hidden hidden sm:block">
                                                <div class="h-full bg-gradient-to-r {{ $item['color'] }} rounded-sm" style="width: {{ min(100, $item['share'] * 5) }}%;"></div>
                                            </div>
                                            <span class="font-bold text-slate-700 w-8 text-right text-[10px]">{{ number_format($item['share'], 1, ',', '.') }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- ======================================================== -->
        <!-- ROW 4: REVENUE TREND & RINCIAN POTENSI PENDAPATAN        -->
        <!-- (Sesuai Desain Acuan: Rincian Berjajar 3 Kolom)          -->
        <!-- ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5 sm:gap-4">
            <!-- Left: Revenue Trend (~55% / 6 cols) -->
            <div class="lg:col-span-6 bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center justify-between pb-2 gap-2">
                    <div class="flex items-center gap-2">
                        <div class="text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h2 class="text-sm sm:text-base font-black text-slate-800 tracking-tight">Revenue Trend</h2>
                    </div>

                    <!-- Legend -->
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>Pendapatan (Rp)</span>
                    </div>
                </div>

                <!-- Canvas Chart Box -->
                <div class="relative w-full h-36 sm:h-40">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>

            <!-- Right: Rincian Potensi Pendapatan (Okt 2026) (~45% / 6 cols) -->
            <!-- 3 Item Ditata Berjajar Horisontal Sesuai Desain Acuan -->
            <div class="lg:col-span-6 bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <div class="w-6 h-6 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                    </div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-800 tracking-tight">Rincian Potensi Pendapatan <span class="text-xs font-bold text-amber-600">({{ $revenueDetail['period'] }})</span></h3>
                </div>

                <!-- 3 Kolom Berdampingan: Total, Domestik, Internasional -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 my-auto py-1">
                    <!-- 1. Total -->
                    <div class="bg-amber-50/60 rounded-xl p-3 border border-amber-200/50 flex flex-col justify-between space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Potensi</p>
                        </div>
                        <h4 class="text-sm sm:text-[15px] font-black text-slate-900 tracking-tight leading-tight">Rp {{ number_format($revenueDetail['total']['value'], 0, ',', '.') }}</h4>
                        <div class="flex items-center justify-between pt-0.5">
                            <div class="flex items-center gap-1 text-[10px] font-bold {{ str_starts_with($revenueDetail['total']['growth'], '+') ? 'text-emerald-600' : 'text-rose-600' }}">
                                <span>{{ str_starts_with($revenueDetail['total']['growth'], '+') ? '▲' : '▼' }}</span>
                                <span>{{ $revenueDetail['total']['growth'] }}</span>
                            </div>
                            <!-- Sparkline -->
                            <div class="flex items-end gap-0.5 h-5">
                                @foreach($revenueDetail['total']['sparkline'] as $bar)
                                    <div class="w-1 bg-gradient-to-t from-amber-500 to-yellow-300 rounded-t-xs" style="height: {{ $bar }}%;"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- 2. Domestik -->
                    <div class="bg-blue-50/50 rounded-xl p-3 border border-blue-100/70 flex flex-col justify-between space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-600 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Domestik</p>
                        </div>
                        <h4 class="text-sm sm:text-[15px] font-black text-slate-900 tracking-tight leading-tight">Rp {{ number_format($revenueDetail['domestik']['value'], 0, ',', '.') }}</h4>
                        <div class="flex items-center justify-between pt-0.5">
                            <div class="text-[10px] font-bold {{ str_starts_with($revenueDetail['domestik']['growth'], '+') ? 'text-emerald-600' : 'text-slate-600' }}">
                                <span class="font-black text-blue-600">{{ $revenueDetail['domestik']['share'] }}%</span>
                                <span class="text-slate-400 font-normal">| {{ $revenueDetail['domestik']['growth'] }}</span>
                            </div>
                            <!-- Sparkline -->
                            <div class="flex items-end gap-0.5 h-5">
                                @foreach($revenueDetail['domestik']['sparkline'] as $bar)
                                    <div class="w-1 bg-gradient-to-t from-blue-600 to-sky-400 rounded-t-xs" style="height: {{ $bar }}%;"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- 3. Internasional -->
                    <div class="bg-indigo-50/50 rounded-xl p-3 border border-indigo-100/70 flex flex-col justify-between space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Internasional</p>
                        </div>
                        <h4 class="text-sm sm:text-[15px] font-black text-slate-900 tracking-tight leading-tight">Rp {{ number_format($revenueDetail['internasional']['value'], 0, ',', '.') }}</h4>
                        <div class="flex items-center justify-between pt-0.5">
                            <div class="text-[10px] font-bold {{ str_starts_with($revenueDetail['internasional']['growth'], '+') ? 'text-emerald-600' : 'text-slate-600' }}">
                                <span class="font-black text-amber-600">{{ $revenueDetail['internasional']['share'] }}%</span>
                                <span class="text-slate-400 font-normal">| {{ $revenueDetail['internasional']['growth'] }}</span>
                            </div>
                            <!-- Sparkline -->
                            <div class="flex items-end gap-0.5 h-5">
                                @foreach($revenueDetail['internasional']['sparkline'] as $bar)
                                    <div class="w-1 bg-gradient-to-t from-indigo-600 to-cyan-400 rounded-t-xs" style="height: {{ $bar }}%;"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-1.5 border-t border-slate-50 text-right">
                    <span class="text-[10px] font-semibold text-slate-400">Total Komposisi Pendapatan Bulan Ini</span>
                </div>
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
                gap: 10px !important;
                padding-bottom: 8px !important;
            }
            body.tv-mode-active .dashboard-screen-container > * + * {
                margin-top: 0 !important;
            }
        }
    </style>

    <!-- ======================================================== -->
    <!-- CHARTS INITIALIZATION SCRIPT                             -->
    <!-- ======================================================== -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Register DataLabels if available
            if (typeof ChartDataLabels !== 'undefined') {
                Chart.register(ChartDataLabels);
            }

            // -------------------------------------------------------------
            // 1. TREN TRAFFIC CHART (Combo: Stacked Bar + Overlaid Line)
            // -------------------------------------------------------------
            const ctxTraffic = document.getElementById('trafficTrendChart');
            if (ctxTraffic) {
                const labels = @json($trafficTrend['labels']);
                const domestikData = @json($trafficTrend['domestik']);
                const internasionalData = @json($trafficTrend['internasional']);
                const totalFlightData = @json($trafficTrend['total_flight']);

                new Chart(ctxTraffic, {
                    data: {
                        labels: labels,
                        datasets: [
                            // Total Flight Line
                            {
                                type: 'line',
                                label: 'Total Flight',
                                data: totalFlightData,
                                borderColor: '#10b981',
                                backgroundColor: '#10b981',
                                borderWidth: 2.5,
                                tension: 0.2,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                order: 1,
                                datalabels: {
                                    display: false
                                }
                            },
                            // Domestik Stacked Bar
                            {
                                type: 'bar',
                                label: 'Domestik',
                                data: domestikData,
                                backgroundColor: '#0070f3',
                                borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 },
                                stack: 'trafficStack',
                                barPercentage: 0.55,
                                categoryPercentage: 0.7,
                                order: 2,
                                datalabels: { display: false }
                            },
                            // Internasional Stacked Bar
                            {
                                type: 'bar',
                                label: 'Internasional',
                                data: internasionalData,
                                backgroundColor: '#f59e0b',
                                borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                                stack: 'trafficStack',
                                barPercentage: 0.55,
                                categoryPercentage: 0.7,
                                order: 3,
                                datalabels: { display: false }
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
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 12 },
                                padding: 8,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += new Intl.NumberFormat('id-ID').format(context.raw);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: { size: 11, weight: '600' },
                                    color: '#64748b'
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                suggestedMax: 10000,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('id-ID').format(value);
                                    },
                                    font: { size: 10 },
                                    color: '#94a3b8'
                                },
                                grid: {
                                    color: '#f1f5f9'
                                },
                                title: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // -------------------------------------------------------------
            // 2. KOMPOSISI TRAFFIC DONUT CHART
            // -------------------------------------------------------------
            const ctxDonut = document.getElementById('trafficDonutChart');
            if (ctxDonut) {
                const domPct = {{ $trafficComposition['domestik']['percentage'] }};
                const intlPct = {{ $trafficComposition['internasional']['percentage'] }};

                new Chart(ctxDonut, {
                    type: 'doughnut',
                    data: {
                        labels: ['Domestik', 'Internasional'],
                        datasets: [{
                            data: [domPct, intlPct],
                            backgroundColor: ['#0070f3', '#f59e0b'],
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 4,
                            cutout: '72%'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.label}: ${context.raw}%`;
                                    }
                                }
                            },
                            datalabels: {
                                color: '#ffffff',
                                font: {
                                    weight: 'bold',
                                    size: 11
                                },
                                formatter: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                });
            }

            // -------------------------------------------------------------
            // 3. REVENUE TREND LINE CHART
            // -------------------------------------------------------------
            const ctxRevenue = document.getElementById('revenueTrendChart');
            if (ctxRevenue) {
                const revLabels = @json($revenueTrend['labels']);
                const revValues = @json($revenueTrend['values']);

                const gradient = ctxRevenue.getContext('2d').createLinearGradient(0, 0, 0, 160);
                gradient.addColorStop(0, 'rgba(16, 185, 129, 0.28)');
                gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

                new Chart(ctxRevenue, {
                    type: 'line',
                    data: {
                        labels: revLabels,
                        datasets: [{
                            label: 'Pendapatan (Milyar Rp)',
                            data: revValues,
                            borderColor: '#10b981',
                            borderWidth: 2.5,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            datalabels: {
                                display: false
                            }
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 12 },
                                padding: 8,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return `Pendapatan: ${context.raw} M (Rp ${(context.raw * 1000000000).toLocaleString('id-ID')})`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: { size: 10, weight: '600' },
                                    color: '#64748b'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                suggestedMax: 20,
                                ticks: {
                                    callback: function(value) {
                                        return value + ' M';
                                    },
                                    font: { size: 10 },
                                    color: '#94a3b8'
                                },
                                grid: {
                                    color: '#f1f5f9'
                                },
                                title: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
