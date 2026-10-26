<x-app-layout>
@php
// Pastikan $financeData dari controller sesuai spesifikasi
$financeData = $financeData ?? [];
$firstTab = 'Summary'; // Summary jadi tab default

if (!function_exists('fmtCard')) {
    function fmtCard($v) {
        return 'Rp ' . number_format(round($v), 0, ',', '.');
    }
}
if (!function_exists('fmtShort')) {
    function fmtShort($v) {
        if ($v >= 1e9) return number_format($v/1e9, 1, ',', '.') . ' M';
        if ($v >= 1e6) return number_format($v/1e6, 1, ',', '.') . ' Jt';
        if ($v >= 1e3) return number_format($v/1e3, 0, ',', '.') . ' Rb';
        return $v;
    }
}

// ===== Hitung agregat Summary (semua cabang) =====
$sumRkap    = $sumRkap ?? 0;
$sumRelease = $sumRelease ?? 0;
$sumCommit  = $sumCommit ?? 0;
$sumConsume = $sumConsume ?? 0;
$sumAvail   = $sumAvail ?? 0;
$allItems   = []; // kumpulan semua item lintas cabang

foreach ($financeData as $branchName => $b) {
    foreach ($b['items'] ?? [] as $itm) {
        $itm['branch_name'] = $itm['branch_name'] ?? $branchName;
        $allItems[] = $itm;
    }
}


$sumSRPct   = $sumRkap    > 0 ? ($sumRelease / $sumRkap    * 100) : 0;
$sumSCPct   = $sumRelease > 0 ? ($sumConsume  / $sumRelease * 100) : 0;
$sumSComPct = $sumRelease > 0 ? ($sumCommit   / $sumRelease * 100) : 0;
$sumSAPct   = $sumRelease > 0 ? ($sumAvail    / $sumRelease * 100) : 0;

// ===== Helper & Grouping Logic untuk Funds Center (Summary) =====
if (!function_exists('categorizeFundsCenter')) {
    /**
     * Mengkategorikan item SAP ke 4 kategori: Pemeliharaan | Perlengkapan | Utilitas | Umum.
     * 
     * ATURAN KATEGORISASI:
     * 1. PRIORITAS 1: Berdasarkan Kode Akun (6 digit pertama)
     *    - 510202 -> Pemeliharaan (Termasuk Kalibrasi 5102029000)
     *    - 510203 -> Perlengkapan
     *    - 510204 -> Utilitas
     *    - Selain itu -> Umum (misal 510205, 510206, dll)
     * 2. PRIORITAS 2: Fallback berdasarkan Nama (jika kode tidak ada/tidak valid)
     *    - Normalisasi nama: lowercase, hapus prefix H-Beban/U-Beban, bersihkan spasi.
     *    - Urutan pencocokan (first match wins):
     *      a. Perlengkapan: mengandung 'perlengkapan', 'plkpn', 'plgkpn', 'plengkapan' (misal: ATK & Cetakan Umum)
     *      b. Pemeliharaan: mengandung 'pemeliharaan', 'pmlh', 'pemel', 'kalibrasi'
     *      c. Utilitas: mengandung 'utilitas', 'pemakaian'
     *      d. Umum: fallback terakhir (jangan deteksi hanya karena ada kata 'umum')
     */
    function categorizeFundsCenter($kode, $nama): string
    {
        $cleanKode = trim((string)$kode);
        
        // Cek PRIORITAS 1: Kode Akun (paling akurat)
        if (preg_match('/^\d{6}/', $cleanKode, $matches)) {
            $prefix = $matches[0];
            if ($prefix === '510202') {
                return 'Pemeliharaan';
            } elseif ($prefix === '510203') {
                return 'Perlengkapan';
            } elseif ($prefix === '510204') {
                return 'Utilitas';
            } else {
                return 'Umum';
            }
        }

        // Cek PRIORITAS 2: Fallback Nama (case-insensitive & clean space)
        $cleanNama = strtolower(trim((string)$nama));
        $cleanNama = preg_replace('/^[hu]-beban\s+/i', '', $cleanNama);
        $cleanNama = preg_replace('/\s+/', ' ', $cleanNama);

        // 1. Cek Perlengkapan Dulu (misal: "Perlengkapan Kep. A.T.K. dan Cetakan Umum" tidak masuk Umum)
        if (str_contains($cleanNama, 'perlengkapan') || 
            str_contains($cleanNama, 'plkpn') || 
            str_contains($cleanNama, 'plgkpn') || 
            str_contains($cleanNama, 'plengkapan')) {
            return 'Perlengkapan';
        }

        // 2. Cek Pemeliharaan (Kalibrasi masuk sini jika kode tidak ada)
        if (str_contains($cleanNama, 'pemeliharaan') || 
            str_contains($cleanNama, 'pmlh') || 
            str_contains($cleanNama, 'pemel') || 
            str_contains($cleanNama, 'kalibrasi')) {
            return 'Pemeliharaan';
        }

        // 3. Cek Utilitas
        if (str_contains($cleanNama, 'utilitas') || 
            str_contains($cleanNama, 'pemakaian')) {
            return 'Utilitas';
        }

        // 4. Default Fallback
        return 'Umum';
    }
}

// Urutan tampil group yang tetap: Umum, Pemeliharaan, Utilitas, Perlengkapan
$groupedItems = [
    'Umum'         => [],
    'Pemeliharaan' => [],
    'Utilitas'     => [],
    'Perlengkapan' => [],
];

$groupColors = [
    'Umum'         => 'blue',
    'Pemeliharaan' => 'amber',
    'Utilitas'     => 'emerald',
    'Perlengkapan' => 'violet',
];

foreach ($allItems as $itm) {
    $cat = categorizeFundsCenter($itm['code'] ?? '', $itm['name'] ?? '');
    $groupedItems[$cat][] = $itm;
}
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    Chart.defaults.devicePixelRatio = Math.max(window.devicePixelRatio||1, 2);
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#475569';
    Chart.defaults.font.family = 'Segoe UI, system-ui, sans-serif';

    Chart.defaults.plugins.tooltip.bodyFont = { size: 12, family: 'Segoe UI, system-ui' };
    Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: 'bold', family: 'Segoe UI, system-ui' };
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.boxPadding = 5;

// Custom plugin: center text donut (Proporsional sesuai ukuran canvas)
const donutCenterTextPlugin = {
    id: 'donutCenterText',
    afterDraw: (chart) => {
        if (chart.config.type !== 'doughnut') return;
        if (!chart.config.options.plugins.donutCenterText) return;
        const opts = chart.config.options.plugins.donutCenterText;
        if (!opts.text) return;
        const ctx = chart.ctx;
        const width = chart.width;
        const height = chart.height;
        ctx.restore();
        
        // Ukuran font adaptif dengan batas maksimal dan minimal
        const fontSizeVal = Math.max(22, Math.min(34, Math.round(height * 0.13)));
        const fontSizeLbl = Math.max(11, Math.min(14, Math.round(fontSizeVal * 0.44)));
        
        ctx.font = `900 ${fontSizeVal}px 'Segoe UI', system-ui`;
        ctx.textBaseline = "middle";
        ctx.textAlign = "center";
        ctx.fillStyle = '#0f172a';
        let textX = width / 2;
        let textY = height / 2 - (fontSizeLbl * 0.5);
        ctx.fillText(opts.text, textX, textY);
        ctx.font = `600 ${fontSizeLbl}px 'Segoe UI', system-ui`;
        ctx.fillStyle = '#64748b';
        ctx.fillText(opts.label, textX, textY + (fontSizeVal * 0.75));
        ctx.save();
    }
};
Chart.register(donutCenterTextPlugin);

// Custom tooltip positioner to show tooltip outside the donut
Chart.Tooltip.positioners.outer = function(elements, eventPosition) {
    if (!elements.length) return false;
    const arc = elements[0].element;
    if (!arc || arc.outerRadius === undefined) return false;
    const angle = (arc.startAngle + arc.endAngle) / 2;
    const r = arc.outerRadius;
    
    let xAlign = 'center';
    let yAlign = 'center';
    
    const cos = Math.cos(angle);
    const sin = Math.sin(angle);
    
    if (cos > 0.1) xAlign = 'left';
    else if (cos < -0.1) xAlign = 'right';
    
    if (sin > 0.5) yAlign = 'top';
    else if (sin < -0.5) yAlign = 'bottom';
    
    return {
        x: arc.x + cos * r,
        y: arc.y + sin * r,
        xAlign: xAlign,
        yAlign: yAlign
    };
};

document.addEventListener('alpine:init', () => {
@foreach($financeData as $branchName => $branch)
@php
    $bid = str_replace([' ','-'], '_', Str::slug($branchName));
@endphp
    Alpine.data('fin_{{ $bid }}', () => ({
        rawData: {!! json_encode($branch['items'] ?? []) !!},
        selRC: null,
        selCA: null,
        openRC: false,
        openCA: false,
        chartRC: null,
        chartCA: null,
        chartBarTopC: null,
        chartBarTopA: null,
        searchQuery: '',
        sortCol: 'consume',
        sortDir: 'desc',
        
        init() {
            if(this.rawData.length > 0) {
                this.selRC = 0;
                this.selCA = 0;
            }
            this.$watch('activeTab', tab => {
                if(tab === '{{ $branchName }}') {
                    this.$nextTick(() => { this.renderAllCharts(); });
                }
            });
            if(this.activeTab === '{{ $branchName }}') {
                this.$nextTick(() => { this.renderAllCharts(); });
            }
        },
        
        renderAllCharts() {
            this.renderDonutRC();
            this.renderDonutCA();
            this.renderBarC();
            this.renderBarA();
        },
        
        destroyChart(chartVar) {
            if(this[chartVar]) { this[chartVar].destroy(); this[chartVar] = null; }
        },
        
        fmt(v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); },
        
        shortFmt(v) {
            let num = parseFloat(v);
            if (num >= 1e9) return (num/1e9).toLocaleString('id-ID',{minimumFractionDigits:1,maximumFractionDigits:1}) + ' M';
            if (num >= 1e6) return (num/1e6).toLocaleString('id-ID',{minimumFractionDigits:1,maximumFractionDigits:1}) + ' Jt';
            if (num >= 1e3) return (num/1e3).toLocaleString('id-ID',{maximumFractionDigits:0}) + ' Rb';
            return num;
        },
        
        pct(a,b) { return b > 0 ? (a/b*100) : 0; },
        
        renderDonutRC() {
            if(this.selRC === null || !this.rawData[this.selRC]) return;
            this.destroyChart('chartRC');
            const item = this.rawData[this.selRC];
            const p = this.pct(item.consume, item.release);
            const ctx = document.getElementById('rc_{{ $bid }}');
            if(!ctx) return;
            this.chartRC = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Release Budget', 'Total Consume'],
                    datasets: [{
                        data: [item.release, item.consume],
                        backgroundColor: ['#1e40af', '#93c5fd'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                        tooltip: { position: 'outer', callbacks: { label: c => c.label + ': ' + this.fmt(c.raw) } },
                        donutCenterText: { text: p.toFixed(1)+'%', label: 'Serapan' }
                    }
                }
            });
        },
        
        renderDonutCA() {
            if(this.selCA === null || !this.rawData[this.selCA]) return;
            this.destroyChart('chartCA');
            const item = this.rawData[this.selCA];
            const p = this.pct(item.available, item.release);
            const ctx = document.getElementById('ca_{{ $bid }}');
            if(!ctx) return;
            this.chartCA = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Total Consume', 'Available Budget'],
                    datasets: [{
                        data: [item.consume, item.available],
                        backgroundColor: ['#1e40af', '#93c5fd'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                        tooltip: { position: 'outer', callbacks: { label: c => c.label + ': ' + this.fmt(c.raw) } },
                        donutCenterText: { text: p.toFixed(1)+'%', label: 'Tersisa' }
                    }
                }
            });
        },
        
        renderBarC() {
            this.destroyChart('chartBarTopC');
            const ctx = document.getElementById('bar_c_{{ $bid }}');
            if(!ctx) return;
            const top = [...this.rawData].sort((a,b)=>b.consume - a.consume);
            this.chartBarTopC = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top.map(x=>x.name),
                    datasets: [{ data: top.map(x=>x.consume), backgroundColor: '#f97316', borderRadius: 4 }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: c => this.fmt(c.raw) } }
                    },
                    scales: { x: { ticks: { callback: v => this.shortFmt(v) } } }
                }
            });
        },
        
        renderBarA() {
            this.destroyChart('chartBarTopA');
            const ctx = document.getElementById('bar_a_{{ $bid }}');
            if(!ctx) return;
            const top = [...this.rawData].sort((a,b)=>b.available - a.available);
            this.chartBarTopA = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top.map(x=>x.name),
                    datasets: [{ data: top.map(x=>x.available), backgroundColor: '#16a34a', borderRadius: 4 }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: c => this.fmt(c.raw) } }
                    },
                    scales: { x: { ticks: { callback: v => this.shortFmt(v) } } }
                }
            });
        },
        
        get filteredRows() {
            let res = this.rawData;
            if(this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                res = res.filter(x => x.name.toLowerCase().includes(q) || x.code.toLowerCase().includes(q));
            }
            return res.sort((a,b) => {
                let va, vb;
                if (this.sortCol === 'serapan_pct') {
                    va = this.pct(a.consume, a.release);
                    vb = this.pct(b.consume, b.release);
                } else {
                    va = a[this.sortCol];
                    vb = b[this.sortCol];
                }
                return this.sortDir === 'desc' ? vb - va : va - vb;
            });
        },
        
        setSort(col) {
            if(this.sortCol === col) this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
            else { this.sortCol = col; this.sortDir = 'desc'; }
        },
        
        sortIcon(col) {
            if(this.sortCol !== col) return '↕';
            return this.sortDir === 'desc' ? '↓' : '↑';
        }
    }));
@endforeach

@foreach($groupedItems as $catName => $catItems)
@php
    $catId = str_replace([' ','-'], '_', Str::slug($catName));
@endphp
    Alpine.data('sum_cat_{{ $catId }}', () => ({
        isOpen: false,
        rawData: {!! json_encode($catItems) !!},
        sortCol: '',
        sortDir: 'desc',
        pct(a,b) { return b > 0 ? (a/b*100) : 0; },
        fmt(v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); },
        get sortedRows() {
            if (!this.sortCol) return this.rawData;
            return [...this.rawData].sort((a,b) => {
                let va, vb;
                if (this.sortCol === 'serapan_pct') {
                    va = this.pct(a.consume, a.release);
                    vb = this.pct(b.consume, b.release);
                } else {
                    va = a[this.sortCol] ?? 0;
                    vb = b[this.sortCol] ?? 0;
                }
                if (typeof va === 'string') {
                    return this.sortDir === 'desc' ? vb.localeCompare(va) : va.localeCompare(vb);
                }
                return this.sortDir === 'desc' ? vb - va : va - vb;
            });
        },
        setSort(col) {
            if(this.sortCol === col) this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
            else { this.sortCol = col; this.sortDir = 'desc'; }
        },
        sortIcon(col) {
            if(this.sortCol !== col) return '↕';
            return this.sortDir === 'desc' ? '↓' : '↑';
        }
    }));
@endforeach
});

// ===== Summary donut charts (rendered after DOM ready) =====
document.addEventListener('DOMContentLoaded', () => {
    // Summary RC donut
    const sumRCCtx = document.getElementById('sum_rc');
    if (sumRCCtx) {
        const release = {{ $sumRelease }};
        const consume = {{ $sumConsume }};
        const pct = release > 0 ? (consume/release*100) : 0;
        new Chart(sumRCCtx, {
            type: 'doughnut',
            data: {
                labels: ['Release Budget', 'Total Consume'],
                datasets: [{ data: [release, consume], backgroundColor: ['#1e40af','#93c5fd'], borderWidth: 3, borderColor: '#fff' }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                    tooltip: { position: 'outer', callbacks: { label: c => 'Rp ' + Math.round(c.raw).toLocaleString('id-ID') } },
                    donutCenterText: { text: pct.toFixed(1)+'%', label: 'Serapan' }
                }
            }
        });
    }

    // Summary CA donut
    const sumCACtx = document.getElementById('sum_ca');
    if (sumCACtx) {
        const release = {{ $sumRelease }};
        const consume = {{ $sumConsume }};
        const avail   = {{ $sumAvail }};
        const pct = release > 0 ? (avail/release*100) : 0;
        new Chart(sumCACtx, {
            type: 'doughnut',
            data: {
                labels: ['Total Consume', 'Available Budget'],
                datasets: [{ data: [consume, avail], backgroundColor: ['#1e40af','#93c5fd'], borderWidth: 3, borderColor: '#fff' }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                    tooltip: { position: 'outer', callbacks: { label: c => 'Rp ' + Math.round(c.raw).toLocaleString('id-ID') } },
                    donutCenterText: { text: pct.toFixed(1)+'%', label: 'Tersisa' }
                }
            }
        });
    }
});
</script>

<div class="w-full" x-data="{ activeTab: '{{ $firstTab }}' }">

    <!-- ================================================================= -->
    <!-- BROADCAST TICKER BAR (Hanya Tampil Saat Mode TV Fullscreen)        -->
    <!-- ================================================================= -->
    <div id="tv-broadcast-bar" class="hidden items-center justify-between px-3 sm:px-5 py-2 bg-slate-900 text-white border-b border-slate-800 shadow-xl z-50">
        <!-- Left: AirNav DARSANA Brand & Title -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="font-black text-sm tracking-wider text-white">DARSANA</span>
                <span class="text-[9px] uppercase font-black tracking-widest bg-indigo-600 px-2 py-0.5 rounded text-white shadow-xs">FINANCE TV</span>
            </div>
            <span class="text-slate-600 hidden sm:inline">|</span>
            <div class="hidden sm:flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Budget Usage Monitoring</span>
                @if($financeUpdatedAt)
                    <span class="text-[10px] bg-slate-800 text-slate-300 border border-slate-700 px-2 py-0.5 rounded font-mono font-medium">Data: {{ $financeUpdatedAt }}</span>
                @endif
                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">Live</span>
            </div>
        </div>

        <!-- Center: Quick Switch Tab in TV Mode -->
        <div class="flex items-center gap-1 bg-slate-800/90 p-1 rounded-xl border border-slate-700/60 shadow-inner max-w-[45vw] overflow-x-auto" style="scrollbar-width: none;">
            <button type="button" @click="activeTab = 'Summary'"
                    :class="activeTab === 'Summary' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white'"
                    class="px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap">
                Summary
            </button>
            @foreach($financeData as $bName => $bVal)
                <button type="button" @click="activeTab = '{{ $bName }}'"
                        :class="activeTab === '{{ $bName }}' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white'"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap">
                    {{ Str::limit($bName, 18) }}
                </button>
            @endforeach
        </div>

        <!-- Right: Real-time Digital Clock & Exit Button -->
        <div class="flex items-center gap-3 sm:gap-4">
            <div class="text-right">
                <div id="tv-live-clock" class="text-xs sm:text-sm font-mono font-black text-amber-400 tracking-wider">00:00:00 WIB</div>
                <div id="tv-live-date" class="hidden sm:block text-[9px] text-slate-400 font-medium tracking-tight">Memuat tanggal...</div>
            </div>

            <!-- Exit Fullscreen TV Button -->
            <button type="button" 
                    onclick="exitFinanceTvMode()" 
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
    <div id="finance-standard-header" class="px-3 sm:px-6 lg:px-8 mb-6 pt-2">
        <div id="standard-header-banner" class="relative overflow-hidden rounded-2xl bg-slate-900 shadow-lg border border-slate-200/50 transition-all duration-300">
            <!-- Background Image with Gradient Overlay -->
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1542296332-2e4473faf563?auto=format&fit=crop&w=1600&q=80" 
                     alt="Airport Tarmac" 
                     class="w-full h-full object-cover object-center opacity-40 mix-blend-luminosity scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 via-slate-900/80 to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
            </div>

            <!-- Header Content: Responsive HP & Desktop -->
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between p-3.5 sm:p-5 gap-3.5 sm:gap-4">
                <!-- Navigation Tabs (Summary & Cabang) - Compact & Elegan -->
                <div class="flex items-center gap-1 sm:gap-1.5 bg-white/95 backdrop-blur-md p-1 rounded-xl shadow-sm border border-white/60 w-full md:w-fit overflow-x-auto max-w-full md:max-w-[62vw]" style="scrollbar-width: none;">
                    
                    <!-- Summary Tab -->
                    <button type="button" @click="activeTab = 'Summary'" 
                            :class="activeTab === 'Summary' ? 'font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm ring-1 ring-blue-500/20' : 'font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/70'"
                            class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Summary</span>
                    </button>

                    <!-- Cabang Tabs -->
                    @foreach($financeData as $branchName => $branch)
                    <button type="button" @click="activeTab = '{{ $branchName }}'" 
                            title="{{ $branchName }}"
                            :class="activeTab === '{{ $branchName }}' ? 'font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm ring-1 ring-blue-500/20' : 'font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/70'"
                            class="flex-shrink-0 flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs transition-all duration-200 whitespace-nowrap">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-75" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ str_replace(['Cabang Pembantu ', 'Cabang '], ['KCP ', ''], $branchName) }}</span>
                    </button>
                    @endforeach
                </div>

                <!-- Right Side: Status Badge, Refresh Button & TV FULLSCREEN BUTTON -->
                <div class="flex items-center justify-between md:justify-end gap-2.5 sm:gap-3 text-white flex-wrap sm:flex-nowrap" x-data="{ refreshing: false }">
                    <!-- Status / Update Badge -->
                    <div class="hidden lg:flex items-center gap-2 bg-black/35 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/10">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-xs text-slate-300 font-medium flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-blue-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Update: <span class="font-bold text-amber-300">{{ $financeUpdatedAt ?? 'Hari Ini' }}</span></span>
                        </span>
                        <span class="text-xs text-slate-400">|</span>
                        <span class="text-xs font-medium text-slate-300">
                            Pukul <span id="darsana-last-update-time">{{ $financeLastUpdateTime }}</span>
                        </span>
                    </div>

                    <!-- Tombol Refresh Data SAP -->
                    @auth
                    <form action="{{ route('finance.refresh') }}" method="POST" @submit.prevent="
                        refreshing = true;
                        fetch($el.action, {
                            method: 'POST',
                            body: new FormData($el),
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(res => {
                            if (res.ok) { window.location.reload(); }
                            else { refreshing = false; alert('Gagal refresh data SAP.'); }
                        })
                        .catch(err => { refreshing = false; alert('Error: ' + err); })
                    " class="m-0">
                        @csrf
                        <button type="submit"
                            :disabled="refreshing"
                            title="Tarik data realisasi anggaran terbaru dari SAP"
                            class="flex items-center gap-2 px-3.5 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-white/15 hover:bg-white/25 backdrop-blur-md text-white border border-white/20 shadow-sm transition-all cursor-pointer disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="hidden sm:inline" x-show="!refreshing">Refresh SAP</span>
                            <span x-show="refreshing" x-cloak>Memuat...</span>
                        </button>
                    </form>
                    @endauth

                    <!-- Tombol Tampil di TV Fullscreen -->
                    <button type="button" 
                            onclick="enterFinanceTvMode()" 
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

        @if(session('success'))
            <div class="mt-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-3.5 rounded-xl shadow-sm text-xs font-semibold" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-800 p-3.5 rounded-xl shadow-sm text-xs font-semibold" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif
    </div>

    <div id="finance-main-content" class="px-4 sm:px-6 lg:px-8 w-full transition-all duration-300">

    {{-- ================================================== --}}
    {{-- TAB SUMMARY --}}
    {{-- ================================================== --}}
    <div x-show="activeTab === 'Summary'" style="display:none;" class="space-y-6">

        {{-- Header Summary dengan badge tanggal update --}}
        <!-- <div class="flex items-center gap-3 flex-wrap">
            @if($financeUpdatedAt)
            <div class="inline-flex items-center gap-2">
                <span class="inline-flex items-center bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wide">Updated</span>
                <span class="inline-flex items-center bg-slate-100 text-slate-600 text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wide">
                    {{ $financeUpdatedAt }} &nbsp;&nbsp;{{ optional(\App\Models\ImportLog::latest()->first())?->created_at?->timezone('Asia/Jakarta')?->format('H:i') ?? now('Asia/Jakarta')->format('H:i') }} WIB &nbsp;&nbsp;&bull;&nbsp;&nbsp; {{ count($financeData) }} CABANG
                </span>
            </div>
            @else
            <div class="inline-flex items-center gap-2">
                <span class="inline-flex items-center bg-slate-50 border border-slate-200 text-slate-500 text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wide">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse mr-1.5"></span>
                    {{ count($financeData) }} Cabang
                </span>
            </div>
            @endif
        </div> -->

        {{-- KPI Cards — 5 Kolom Sejajar di Layar Besar / TV (RKAP, Release, Consume, Commitment, Available) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#0f172a] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">RKAP</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($sumRkap) }}</p>
                <p class="text-xs font-semibold text-transparent mt-1.5 select-none">&nbsp;</p>
            </div>
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#2563eb] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Release Budget</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($sumRelease) }}</p>
                <p class="text-xs font-semibold text-blue-600 mt-1.5">{{ number_format($sumSRPct,1,',','.') }}% dari RKAP</p>
            </div>
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#f97316] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Consume</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($sumConsume) }}</p>
                <p class="text-xs font-semibold text-orange-500 mt-1.5">{{ number_format($sumSCPct,1,',','.') }}% serapan</p>
            </div>
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#7c3aed] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Commitment</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($sumCommit) }}</p>
                <p class="text-xs font-semibold text-violet-500 mt-1.5">{{ number_format($sumSComPct,1,',','.') }}% dari Rel</p>
            </div>
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#16a34a] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Available Budget</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($sumAvail) }}</p>
                <p class="text-xs font-semibold text-emerald-600 mt-1.5">{{ number_format($sumSAPct,1,',','.') }}% dari Rel</p>
            </div>
        </div>

        {{-- Serapan Progress Bar --}}
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col md:flex-row items-center gap-4 sm:gap-6">
            <div class="flex-shrink-0 text-center md:text-left">
                <p class="text-3xl sm:text-4xl font-black {{ $sumSCPct >= 90 ? 'text-[#16a34a]' : ($sumSCPct >= 60 ? 'text-[#2563eb]' : ($sumSCPct >= 30 ? 'text-[#f97316]' : 'text-[#dc2626]')) }}">{{ number_format($sumSCPct,1,',','.') }}%</p>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Serapan Anggaran</p>
            </div>
            <div class="flex-1 w-full">
                <div class="w-full bg-slate-100 h-3.5 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-500 {{ $sumSCPct >= 90 ? 'bg-[#16a34a]' : ($sumSCPct >= 60 ? 'bg-[#2563eb]' : ($sumSCPct >= 30 ? 'bg-[#f97316]' : 'bg-[#dc2626]')) }}" style="width: {{ min(100, $sumSCPct) }}%"></div>
                </div>
                <p class="text-right text-xs sm:text-sm font-bold text-slate-600 mt-1.5">{{ fmtCard($sumConsume) }} dari {{ fmtCard($sumRelease) }}</p>
            </div>
        </div>

        {{-- Donut Charts (Ukuran kompak, proporsional dan tidak memotong konten di bawahnya) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col justify-between">
                <h2 class="text-sm sm:text-base font-black text-slate-800 mb-2">Release vs Total Consume</h2>
                <div class="h-64 sm:h-72 w-full flex-1 relative"><canvas id="sum_rc"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col justify-between">
                <h2 class="text-sm sm:text-base font-black text-slate-800 mb-2">Consume vs Available Budget</h2>
                <div class="h-64 sm:h-72 w-full flex-1 relative"><canvas id="sum_ca"></canvas></div>
            </div>
        </div>

        {{-- Detail Funds Center GROUPED (hanya di Summary) --}}
        <div class="bg-white rounded-[18px] p-6 shadow-sm border border-slate-100">
            <h2 class="text-xl font-black text-slate-800 mb-6">Detail Funds Center – Semua Cabang</h2>

            @php
            $groupIconColors = [
                'Umum'         => ['bg' => 'bg-blue-600',   'text' => 'text-blue-700',   'badge' => 'bg-blue-50 text-blue-700 border-blue-100'],
                'Pemeliharaan' => ['bg' => 'bg-amber-500',  'text' => 'text-amber-700',  'badge' => 'bg-amber-50 text-amber-700 border-amber-100'],
                'Utilitas'     => ['bg' => 'bg-emerald-600','text' => 'text-emerald-700','badge' => 'bg-emerald-50 text-emerald-700 border-emerald-100'],
                'Perlengkapan' => ['bg' => 'bg-violet-600', 'text' => 'text-violet-700', 'badge' => 'bg-violet-50 text-violet-700 border-violet-100'],
            ];
            @endphp

            <div class="space-y-4">
            @foreach($groupedItems as $catName => $catItems)
            @if(count($catItems) === 0) @continue @endif
            @php
                $catRkap    = array_sum(array_column($catItems, 'rkap'));
                $catRelease = array_sum(array_column($catItems, 'release'));
                $catConsume = array_sum(array_column($catItems, 'consume'));
                $catAvail   = array_sum(array_column($catItems, 'available'));
                $catCommit  = array_sum(array_column($catItems, 'commitment'));
                $catPct     = $catRelease > 0 ? ($catConsume / $catRelease * 100) : 0;
                $c          = $groupIconColors[$catName];
            @endphp

            @php
                $catId = str_replace([' ','-'], '_', Str::slug($catName));
            @endphp
            {{-- Group Card with Accordion Toggle --}}
            <div x-data="sum_cat_{{ $catId }}" class="border border-slate-100 rounded-2xl bg-slate-50/10 p-5 shadow-sm transition-all duration-300">
                {{-- Group Header (Clickable) --}}
                <div @click="isOpen = !isOpen" class="flex items-center gap-3 cursor-pointer select-none group">
                    <div class="w-2.5 h-2.5 rounded-full {{ $c['bg'] }}"></div>
                    <h3 class="text-base font-black text-slate-700 uppercase tracking-wide group-hover:text-blue-600 transition-colors">{{ $catName }}</h3>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full border {{ $c['badge'] }}">{{ count($catItems) }} item</span>
                    <span class="ml-auto text-sm font-bold text-slate-500 mr-2">Serapan: <span class="{{ $c['text'] }} font-black">{{ number_format($catPct,1,',','.') }}%</span></span>
                    
                    {{-- Chevron Indicator --}}
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-300 group-hover:text-slate-600" :class="isOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                {{-- Mini progress bar --}}
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-3 mb-2">
                    <div class="h-full {{ $c['bg'] }} rounded-full transition-all duration-500" style="width: {{ min(100,$catPct) }}%"></div>
                </div>

                {{-- Collapsible Table Content --}}
                <div x-show="isOpen" x-collapse style="display: none;" class="mt-4 pt-4 border-t border-slate-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap min-w-[800px]">
                            <thead>
                                <tr class="text-[13px] font-extrabold text-slate-500 uppercase border-b border-slate-200 tracking-wider">
                                    <th class="py-2 px-4 cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('name')">
                                        Funds Center <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('name')"></span>
                                    </th>
                                    <th class="py-2 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('rkap')">
                                        RKAP <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('rkap')"></span>
                                    </th>
                                    <th class="py-2 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('release')">
                                        Release <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('release')"></span>
                                    </th>
                                    <th class="py-2 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('consume')">
                                        Consume <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('consume')"></span>
                                    </th>
                                    <th class="py-2 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('available')">
                                        Available <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('available')"></span>
                                    </th>
                                    <th class="py-2 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('commitment')">
                                        Commit <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('commitment')"></span>
                                    </th>
                                    <th class="py-2 px-4 w-40 cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('serapan_pct')">
                                        Serapan <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('serapan_pct')"></span>
                                    </th>
                                    <th class="py-2 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('serapan_pct')">
                                        % <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('serapan_pct')"></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(r, index) in sortedRows" :key="index">
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3 px-4">
                                            <p class="text-base font-extrabold text-slate-800" x-text="r.name"></p>
                                            <p class="text-sm font-medium text-slate-500 mt-0.5" x-text="r.code + (r.branch_name ? ' • ' + r.branch_name : '')"></p>
                                        </td>
                                        <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.rkap)"></td>
                                        <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.release)"></td>
                                        <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.consume)"></td>
                                        <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.available)"></td>
                                        <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.commitment)"></td>
                                        <td class="py-3.5 px-4">
                                            <div class="w-full bg-slate-200/70 h-3 rounded-full overflow-hidden">
                                                <div class="h-full transition-all duration-300" :class="pct(r.consume,r.release)>=90 ? 'bg-[#16a34a]' : pct(r.consume,r.release)>=60 ? 'bg-[#2563eb]' : pct(r.consume,r.release)>=30 ? 'bg-[#f97316]' : 'bg-[#dc2626]'" :style="'width:'+Math.min(100, pct(r.consume,r.release))+'%'"></div>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-right text-[15px] font-black text-slate-800" x-text="pct(r.consume,r.release).toFixed(1)+'%'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach
            </div>
        </div>

    </div>{{-- end Summary --}}

    {{-- ================================================== --}}
    {{-- TAB PER CABANG (tidak berubah) --}}
    {{-- ================================================== --}}
    @foreach($financeData as $branchName => $branch)
    @php
        $bid = str_replace([' ','-'], '_', Str::slug($branchName));
        $rkap = $branch['rkap'] ?? 0;
        $rel = $branch['release'] ?? 0;
        $com = $branch['commitment'] ?? 0;
        $cons = $branch['consume'] ?? 0;
        $avail = $branch['available'] ?? 0;
        $sRPct = $rkap > 0 ? ($rel/$rkap*100) : 0;
        $sCPct = $rel > 0 ? ($cons/$rel*100) : 0;
        $sComPct = $rel > 0 ? ($com/$rel*100) : 0;
        $sAPct = $rel > 0 ? ($avail/$rel*100) : 0;
    @endphp
    
    <div x-show="activeTab === '{{ $branchName }}'" x-data="fin_{{ $bid }}" style="display:none;" class="space-y-6">
        
        <!-- KPI Cards — 5 Kolom Sejajar di Layar Besar / TV (RKAP, Release, Consume, Commitment, Available) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
            <!-- RKAP -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#0f172a] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">RKAP</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($rkap) }}</p>
                <p class="text-xs font-semibold text-transparent mt-1.5 select-none">&nbsp;</p>
            </div>
            <!-- Release -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#2563eb] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Release Budget</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($rel) }}</p>
                <p class="text-xs font-semibold text-blue-600 mt-1.5">{{ number_format($sRPct,1,',','.') }}% dari RKAP</p>
            </div>
            <!-- Consume -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#f97316] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Consume</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($cons) }}</p>
                <p class="text-xs font-semibold text-orange-500 mt-1.5">{{ number_format($sCPct,1,',','.') }}% serapan</p>
            </div>
            <!-- Commitment -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#7c3aed] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Commitment</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($com) }}</p>
                <p class="text-xs font-semibold text-violet-500 mt-1.5">{{ number_format($sComPct,1,',','.') }}% dari Rel</p>
            </div>
            <!-- Available -->
            <div class="bg-white rounded-2xl p-4 sm:p-4.5 border-l-[5px] border-l-[#16a34a] shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Available Budget</p>
                <p class="text-xl sm:text-2xl font-black text-slate-800 mt-2">{{ fmtCard($avail) }}</p>
                <p class="text-xs font-semibold text-emerald-600 mt-1.5">{{ number_format($sAPct,1,',','.') }}% dari Rel</p>
            </div>
        </div>

        <!-- Serapan Progress -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col md:flex-row items-center gap-4 sm:gap-6">
            <div class="flex-shrink-0 text-center md:text-left">
                <p class="text-3xl sm:text-4xl font-black {{ $sCPct >= 90 ? 'text-[#16a34a]' : ($sCPct >= 60 ? 'text-[#2563eb]' : ($sCPct >= 30 ? 'text-[#f97316]' : 'text-[#dc2626]')) }}">{{ number_format($sCPct,1,',','.') }}%</p>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Serapan Anggaran</p>
            </div>
            <div class="flex-1 w-full">
                <div class="w-full bg-slate-100 h-3.5 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-500 {{ $sCPct >= 90 ? 'bg-[#16a34a]' : ($sCPct >= 60 ? 'bg-[#2563eb]' : ($sCPct >= 30 ? 'bg-[#f97316]' : 'bg-[#dc2626]')) }}" style="width: {{ min(100, $sCPct) }}%"></div>
                </div>
                <p class="text-right text-xs sm:text-sm font-bold text-slate-600 mt-1.5">{{ fmtCard($cons) }} dari {{ fmtCard($rel) }}</p>
            </div>
        </div>

        <!-- Donut Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
            <!-- RC -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 relative flex flex-col justify-between">
                <div class="flex justify-between items-start mb-2">
                    <h2 class="text-sm sm:text-base font-black text-slate-800">Release vs Total Consume</h2>
                    <div class="relative" @click.outside="openRC=false">
                        <button @click="openRC=!openRC" class="text-xs font-bold bg-slate-100 px-3 py-1.5 rounded-md text-slate-600 hover:bg-slate-200 transition">
                            Filter: <span x-text="rawData[selRC]?.name || 'Pilih'"></span>
                        </button>
                        <div x-show="openRC" class="absolute right-0 top-10 w-64 bg-white border border-slate-200 shadow-xl rounded-xl p-3 z-10 max-h-60 overflow-y-auto">
                            <template x-for="(itm, i) in rawData" :key="i">
                                <label class="flex items-center gap-2 p-1.5 hover:bg-slate-50 cursor-pointer rounded">
                                    <input type="radio" name="rc_{{ $bid }}" :value="i" x-model="selRC" @change="renderDonutRC(); openRC=false" class="accent-blue-600">
                                    <span class="text-xs text-slate-700 font-semibold" x-text="itm.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="h-64 sm:h-72 w-full flex-1 relative"><canvas id="rc_{{ $bid }}"></canvas></div>
            </div>
            
            <!-- CA -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-100 relative flex flex-col justify-between">
                <div class="flex justify-between items-start mb-2">
                    <h2 class="text-sm sm:text-base font-black text-slate-800">Consume vs Available Budget</h2>
                    <div class="relative" @click.outside="openCA=false">
                        <button @click="openCA=!openCA" class="text-xs font-bold bg-slate-100 px-3 py-1.5 rounded-md text-slate-600 hover:bg-slate-200 transition">
                            Filter: <span x-text="rawData[selCA]?.name || 'Pilih'"></span>
                        </button>
                        <div x-show="openCA" class="absolute right-0 top-10 w-64 bg-white border border-slate-200 shadow-xl rounded-xl p-3 z-10 max-h-60 overflow-y-auto">
                            <template x-for="(itm, i) in rawData" :key="i">
                                <label class="flex items-center gap-2 p-1.5 hover:bg-slate-50 cursor-pointer rounded">
                                    <input type="radio" name="ca_{{ $bid }}" :value="i" x-model="selCA" @change="renderDonutCA(); openCA=false" class="accent-blue-600">
                                    <span class="text-xs text-slate-700 font-semibold" x-text="itm.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="h-64 sm:h-72 w-full flex-1 relative"><canvas id="ca_{{ $bid }}"></canvas></div>
            </div>
        </div>
        
        <!-- Bar Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-[18px] p-6 shadow-sm border border-slate-100 flex flex-col overflow-y-auto" style="max-height: 800px;">
                <h2 class="text-lg font-black text-slate-800 mb-4 sticky top-0 bg-white z-10 pb-2 border-b border-slate-100">Konsumsi Tertinggi</h2>
                <div :style="'height: ' + Math.max(560, rawData.length * 40) + 'px'" class="w-full mt-2"><canvas id="bar_c_{{ $bid }}"></canvas></div>
            </div>
            <div class="bg-white rounded-[18px] p-6 shadow-sm border border-slate-100 flex flex-col overflow-y-auto" style="max-height: 800px;">
                <h2 class="text-lg font-black text-slate-800 mb-4 sticky top-0 bg-white z-10 pb-2 border-b border-slate-100">Sisa Anggaran Tertinggi</h2>
                <div :style="'height: ' + Math.max(560, rawData.length * 40) + 'px'" class="w-full mt-2"><canvas id="bar_a_{{ $bid }}"></canvas></div>
            </div>
        </div>

        <!-- Detail Table (TIDAK digroup, biarkan apa adanya) -->
        <div class="bg-white rounded-[18px] p-6 shadow-sm border border-slate-100">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h2 class="text-lg font-black text-slate-800">Detail Funds Center</h2>
                <input type="text" x-model="searchQuery" placeholder="Cari item..." class="text-sm border border-slate-200 rounded-lg px-4 py-2 w-full sm:w-64 bg-slate-50 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap min-w-[800px]">
                    <thead>
                        <tr class="text-[13px] font-extrabold text-slate-500 uppercase border-b border-slate-200 tracking-wider">
                            <th class="py-3 px-4 cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('name')">
                                Funds Center <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('name')"></span>
                            </th>
                            <th class="py-3 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('rkap')">
                                RKAP <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('rkap')"></span>
                            </th>
                            <th class="py-3 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('release')">
                                Release <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('release')"></span>
                            </th>
                            <th class="py-3 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('consume')">
                                Consume <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('consume')"></span>
                            </th>
                            <th class="py-3 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('available')">
                                Available <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('available')"></span>
                            </th>
                            <th class="py-3 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('commitment')">
                                Commit <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('commitment')"></span>
                            </th>
                            <th class="py-3 px-4 w-48 cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('serapan_pct')">
                                Serapan <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('serapan_pct')"></span>
                            </th>
                            <th class="py-3 px-4 text-right cursor-pointer hover:text-slate-700 transition select-none" @click="setSort('serapan_pct')">
                                % <span class="ml-1 inline-block text-[10px]" x-text="sortIcon('serapan_pct')"></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(r, index) in filteredRows" :key="index">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4">
                                    <p class="text-base font-extrabold text-slate-800" x-text="r.name"></p>
                                    <p class="text-sm font-medium text-slate-500 mt-0.5" x-text="r.code"></p>
                                </td>
                                <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.rkap)"></td>
                                <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.release)"></td>
                                <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.consume)"></td>
                                <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.available)"></td>
                                <td class="py-3.5 px-4 text-right text-[15px] font-bold text-slate-700" x-text="fmt(r.commitment)"></td>
                                <td class="py-3.5 px-4">
                                    <div class="w-full bg-slate-200/70 h-3 rounded-full overflow-hidden">
                                        <div class="h-full transition-all duration-300" :class="pct(r.consume,r.release)>=90 ? 'bg-[#16a34a]' : pct(r.consume,r.release)>=60 ? 'bg-[#2563eb]' : pct(r.consume,r.release)>=30 ? 'bg-[#f97316]' : 'bg-[#dc2626]'" :style="'width:'+Math.min(100, pct(r.consume,r.release))+'%'"></div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right text-[15px] font-black text-slate-800" x-text="pct(r.consume,r.release).toFixed(1)+'%'"></td>
                            </tr>
                        </template>
                        <template x-if="filteredRows.length === 0">
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400 font-medium">Data tidak ditemukan.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    @endforeach
    
</div>
</div>

<script>
(function() {
    // Simpan waktu import terakhir saat halaman pertama kali dimuat
    let knownLastUpdate = @json($financeLog?->created_at?->toIso8601String());

    function checkForUpdates() {
        fetch('/dashboard/last-update', {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        })
        .then(r => r.json())
        .then(data => {
            if (!data.last_update) return; // belum ada import sama sekali

            if (knownLastUpdate === null) {
                // Halaman dimuat saat belum ada data, sekarang sudah ada → reload
                window.location.reload();
                return;
            }

            if (data.last_update !== knownLastUpdate) {
                // Ada data baru → reload agar angka-angka dashboard ter-update
                window.location.reload();
            }
            // Jika sama → tidak lakukan apa-apa (tidak kedip, tidak reload)
        })
        .catch(err => console.warn('[Darsana] Gagal cek update:', err));
    }

    // Polling setiap 30 detik
    setInterval(checkForUpdates, 30000);
})();

// =================================================================
// FINANCE TV FULLSCREEN CONTROLLER SCRIPT & DIGITAL CLOCK
// =================================================================
function updateFinanceTvClock() {
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
setInterval(updateFinanceTvClock, 1000);
updateFinanceTvClock();

// Enter Finance TV Mode
window.enterFinanceTvMode = function() {
    localStorage.setItem('darsana_finance_tv_mode', 'true');
    document.body.classList.add('tv-mode-active');

    const elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen().catch(() => {});
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen();
    }

    setTimeout(resizeFinanceCharts, 200);
};

// Exit Finance TV Mode
window.exitFinanceTvMode = function() {
    localStorage.removeItem('darsana_finance_tv_mode');
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

    setTimeout(resizeFinanceCharts, 200);
};

// Listen to fullscreen changes & ESC key
document.addEventListener('fullscreenchange', function() {
    if (!document.fullscreenElement) {
        localStorage.removeItem('darsana_finance_tv_mode');
        document.body.classList.remove('tv-mode-active');
        setTimeout(resizeFinanceCharts, 200);
    }
});
document.addEventListener('webkitfullscreenchange', function() {
    if (!document.webkitFullscreenElement) {
        localStorage.removeItem('darsana_finance_tv_mode');
        document.body.classList.remove('tv-mode-active');
        setTimeout(resizeFinanceCharts, 200);
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.body.classList.contains('tv-mode-active')) {
        window.exitFinanceTvMode();
    }
});

function resizeFinanceCharts() {
    if (typeof Chart !== 'undefined' && Chart.instances) {
        Object.values(Chart.instances).forEach(function(chart) {
            try { chart.resize(); } catch (err) {}
        });
    }
}

// Inisialisasi awal jika sebelumnya aktif
if (localStorage.getItem('darsana_finance_tv_mode') === 'true') {
    document.body.classList.add('tv-mode-active');
}
</script>

<style>
/* ================================================================= */
/* MODE TV FULLSCREEN KHUSUS FINANCE                                */
/* ================================================================= */
body.tv-mode-active {
    background-color: #f8fafc !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
}

body.tv-mode-active #main-navigation-bar {
    display: none !important;
}

body.tv-mode-active #finance-standard-header {
    display: none !important;
}

body.tv-mode-active #tv-broadcast-bar {
    display: flex !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 50 !important;
}

body.tv-mode-active main {
    padding: 0 !important;
    margin: 0 !important;
}

/* Penyesuaian saat mode TV aktif di desktop / layar besar (TV) */
@media (min-width: 1024px) {
    body.tv-mode-active #finance-main-content {
        padding-top: 1rem !important;
        padding-bottom: 2.5rem !important;
        padding-left: 2rem !important;
        padding-right: 2rem !important;
    }

    /* Optimasi tinggi container donut chart agar muat proporsional dan tidak mendorong layout terlalu jauh */
    body.tv-mode-active #sum_rc,
    body.tv-mode-active #sum_ca {
        max-height: 280px !important;
    }

    body.tv-mode-active .h-64 {
        height: 260px !important;
    }

    /* Jarak antar blok card dibuat lebih rapat & rapi saat TV mode */
    body.tv-mode-active .space-y-6 > * + * {
        margin-top: 1rem !important;
    }

    /* Padding card sedikit lebih efisien di layar TV */
    body.tv-mode-active .rounded-\[18px\].p-6 {
        padding: 1.25rem 1.5rem !important;
    }
}

/* Pastikan di layar HP / mobile, jika fullscreen aktif ataupun normal, scrolling & layout tetap natural 100% */
@media (max-width: 640px) {
    body.tv-mode-active #finance-main-content {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
        padding-top: 0.75rem !important;
    }
}
</style>

</x-app-layout>
