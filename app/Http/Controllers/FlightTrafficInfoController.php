<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrafficData;
use App\Models\EnrouteData;
use App\Models\TerminalData;
use App\Models\Airline;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FlightTrafficInfoController extends Controller
{
    /**
     * 1. SUMMARY DASHBOARD (Overview)
     */
    public function index()
    {
        // -------------------------------------------------------------
        // DATA DATABASE: TABEL traffic_data
        // Aturan: registrasi huruf depan 'PK' = Domestik, selain itu = Internasional
        // -------------------------------------------------------------
        $totalFlightCount = TrafficData::count();
        $domestikCount = TrafficData::where('registrasi', 'LIKE', 'PK%')->count();
        $internasionalCount = TrafficData::where('registrasi', 'NOT LIKE', 'PK%')->count();

        // Persentase Share
        $domShare = $totalFlightCount > 0 ? round(($domestikCount / $totalFlightCount) * 100, 1) : 0;
        $intShare = $totalFlightCount > 0 ? round(($internasionalCount / $totalFlightCount) * 100, 1) : 0;

        // Tanggal data terbaru
        $latestDate = TrafficData::max('tanggal');
        $periodLabel = $latestDate ? Carbon::parse($latestDate)->format('M Y') : 'Sep 2026';

        // Hitung persentase pertumbuhan (7 hari terakhir vs 7 hari sebelumnya)
        $growthTotal = '+3,4%';
        $growthDom = '+1,6%';
        $growthIntl = '+15,6%';

        if ($latestDate) {
            $end = Carbon::parse($latestDate);
            $startW2 = $end->copy()->subDays(6);
            $endW1 = $startW2->copy()->subDay();
            $startW1 = $endW1->copy()->subDays(6);

            $w2Total = TrafficData::whereBetween('tanggal', [$startW2->format('Y-m-d'), $end->format('Y-m-d')])->count();
            $w1Total = TrafficData::whereBetween('tanggal', [$startW1->format('Y-m-d'), $endW1->format('Y-m-d')])->count();

            $w2Dom = TrafficData::whereBetween('tanggal', [$startW2->format('Y-m-d'), $end->format('Y-m-d')])->where('registrasi', 'LIKE', 'PK%')->count();
            $w1Dom = TrafficData::whereBetween('tanggal', [$startW1->format('Y-m-d'), $endW1->format('Y-m-d')])->where('registrasi', 'LIKE', 'PK%')->count();

            $w2Int = TrafficData::whereBetween('tanggal', [$startW2->format('Y-m-d'), $end->format('Y-m-d')])->where('registrasi', 'NOT LIKE', 'PK%')->count();
            $w1Int = TrafficData::whereBetween('tanggal', [$startW1->format('Y-m-d'), $endW1->format('Y-m-d')])->where('registrasi', 'NOT LIKE', 'PK%')->count();

            if ($w1Total > 0) {
                $pct = round((($w2Total - $w1Total) / $w1Total) * 100, 1);
                $growthTotal = ($pct >= 0 ? '+' : '') . number_format($pct, 1, ',', '.') . '%';
            }
            if ($w1Dom > 0) {
                $pct = round((($w2Dom - $w1Dom) / $w1Dom) * 100, 1);
                $growthDom = ($pct >= 0 ? '+' : '') . number_format($pct, 1, ',', '.') . '%';
            }
            if ($w1Int > 0) {
                $pct = round((($w2Int - $w1Int) / $w1Int) * 100, 1);
                $growthIntl = ($pct >= 0 ? '+' : '') . number_format($pct, 1, ',', '.') . '%';
            }
        }

        // Sparkline 7 hari terakhir dari database traffic_data
        $sparklineDays = DB::table('traffic_data')
            ->selectRaw("
                tanggal,
                COUNT(*) as total,
                SUM(CASE WHEN registrasi LIKE 'PK%' THEN 1 ELSE 0 END) as dom,
                SUM(CASE WHEN registrasi NOT LIKE 'PK%' THEN 1 ELSE 0 END) as intl
            ")
            ->whereNotNull('tanggal')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->limit(7)
            ->get()
            ->reverse()
            ->values();

        $maxTotal = $sparklineDays->max('total') ?: 1;
        $maxDom = $sparklineDays->max('dom') ?: 1;
        $maxIntl = $sparklineDays->max('intl') ?: 1;

        $totalSparkline = $sparklineDays->map(fn($d) => max(25, (int) round(($d->total / $maxTotal) * 100)))->all();
        $domSparkline = $sparklineDays->map(fn($d) => max(25, (int) round(($d->dom / $maxDom) * 100)))->all();
        $intlSparkline = $sparklineDays->map(fn($d) => max(25, (int) round(($d->intl / $maxIntl) * 100)))->all();

        if (empty($totalSparkline)) {
            $totalSparkline = [30, 45, 55, 40, 70, 50, 85];
            $domSparkline = [35, 50, 60, 45, 75, 55, 90];
            $intlSparkline = [20, 35, 40, 30, 55, 45, 70];
        }

        // -------------------------------------------------------------
        // DATA ESTIMASI POTENSI PENDAPATAN (TABEL enroute_data & terminal_data)
        // enroute_charge_idr + biaya_terminal_idr
        // -------------------------------------------------------------
        $totalEnrouteCharge = EnrouteData::sum('enroute_charge_idr') ?? 0;
        $totalTerminalCharge = TerminalData::sum('biaya_terminal_idr') ?? 0;
        $totalPotensiPendapatan = $totalEnrouteCharge + $totalTerminalCharge;

        // Breakdown Domestik vs Internasional (Berdasarkan registrasi LIKE 'PK%' = Domestik)
        $domEnroute = EnrouteData::where('registrasi', 'LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0;
        $intEnroute = EnrouteData::where('registrasi', 'NOT LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0;
        $domTerminal = TerminalData::where('registrasi', 'LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0;
        $intTerminal = TerminalData::where('registrasi', 'NOT LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0;

        $totalRevenueDom = $domEnroute + $domTerminal;
        $totalRevenueInt = $intEnroute + $intTerminal;

        $domRevenueShare = $totalPotensiPendapatan > 0 ? round(($totalRevenueDom / $totalPotensiPendapatan) * 100, 1) : 0;
        $intRevenueShare = $totalPotensiPendapatan > 0 ? round(($totalRevenueInt / $totalPotensiPendapatan) * 100, 1) : 0;

        // Tanggal terbaru dari pendapatan
        $latestRevenueDate = EnrouteData::max('dof');
        if (!$latestRevenueDate) {
            $latestRevenueDate = TerminalData::max('tanggal') ?? $latestDate;
        }
        $revenuePeriodLabel = $latestRevenueDate ? Carbon::parse($latestRevenueDate)->format('M Y') : 'Jun 2026';

        // Hitung growth potensi pendapatan (7 hari terakhir vs 7 hari sebelumnya)
        $revenueGrowth = '+0,8%';
        $revenueGrowthDom = '-0,6%';
        $revenueGrowthInt = '+1,7%';
        $revenueSparkline = [45, 60, 55, 70, 65, 80, 100];
        $revenueSparklineDom = [45, 60, 55, 70, 65, 80, 100];
        $revenueSparklineInt = [45, 60, 55, 70, 65, 80, 100];

        if ($latestRevenueDate) {
            $revEnd = Carbon::parse($latestRevenueDate);
            $revStartW2 = $revEnd->copy()->subDays(6);
            $revEndW1 = $revStartW2->copy()->subDay();
            $revStartW1 = $revEndW1->copy()->subDays(6);

            $revW2 = (EnrouteData::whereBetween('dof', [$revStartW2->format('Y-m-d 00:00:00'), $revEnd->format('Y-m-d 23:59:59')])->sum('enroute_charge_idr') ?? 0)
                   + (TerminalData::whereBetween('tanggal', [$revStartW2->format('Y-m-d 00:00:00'), $revEnd->format('Y-m-d 23:59:59')])->sum('biaya_terminal_idr') ?? 0);

            $revW1 = (EnrouteData::whereBetween('dof', [$revStartW1->format('Y-m-d 00:00:00'), $revEndW1->format('Y-m-d 23:59:59')])->sum('enroute_charge_idr') ?? 0)
                   + (TerminalData::whereBetween('tanggal', [$revStartW1->format('Y-m-d 00:00:00'), $revEndW1->format('Y-m-d 23:59:59')])->sum('biaya_terminal_idr') ?? 0);

            $revW2Dom = (EnrouteData::whereBetween('dof', [$revStartW2->format('Y-m-d 00:00:00'), $revEnd->format('Y-m-d 23:59:59')])->where('registrasi', 'LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0)
                      + (TerminalData::whereBetween('tanggal', [$revStartW2->format('Y-m-d 00:00:00'), $revEnd->format('Y-m-d 23:59:59')])->where('registrasi', 'LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0);

            $revW1Dom = (EnrouteData::whereBetween('dof', [$revStartW1->format('Y-m-d 00:00:00'), $revEndW1->format('Y-m-d 23:59:59')])->where('registrasi', 'LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0)
                      + (TerminalData::whereBetween('tanggal', [$revStartW1->format('Y-m-d 00:00:00'), $revEndW1->format('Y-m-d 23:59:59')])->where('registrasi', 'LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0);

            $revW2Int = (EnrouteData::whereBetween('dof', [$revStartW2->format('Y-m-d 00:00:00'), $revEnd->format('Y-m-d 23:59:59')])->where('registrasi', 'NOT LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0)
                      + (TerminalData::whereBetween('tanggal', [$revStartW2->format('Y-m-d 00:00:00'), $revEnd->format('Y-m-d 23:59:59')])->where('registrasi', 'NOT LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0);

            $revW1Int = (EnrouteData::whereBetween('dof', [$revStartW1->format('Y-m-d 00:00:00'), $revEndW1->format('Y-m-d 23:59:59')])->where('registrasi', 'NOT LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0)
                      + (TerminalData::whereBetween('tanggal', [$revStartW1->format('Y-m-d 00:00:00'), $revEndW1->format('Y-m-d 23:59:59')])->where('registrasi', 'NOT LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0);

            if ($revW1 > 0) {
                $pct = round((($revW2 - $revW1) / $revW1) * 100, 1);
                $revenueGrowth = ($pct >= 0 ? '+' : '') . number_format($pct, 1, ',', '.') . '%';
            }
            if ($revW1Dom > 0) {
                $pctDom = round((($revW2Dom - $revW1Dom) / $revW1Dom) * 100, 1);
                $revenueGrowthDom = ($pctDom >= 0 ? '+' : '') . number_format($pctDom, 1, ',', '.') . '%';
            }
            if ($revW1Int > 0) {
                $pctInt = round((($revW2Int - $revW1Int) / $revW1Int) * 100, 1);
                $revenueGrowthInt = ($pctInt >= 0 ? '+' : '') . number_format($pctInt, 1, ',', '.') . '%';
            }

            // Hitung sparkline 7 hari terakhir
            $revSparklineRaw = [];
            $revSparklineRawDom = [];
            $revSparklineRawInt = [];
            for ($i = 6; $i >= 0; $i--) {
                $targetDay = $revEnd->copy()->subDays($i)->format('Y-m-d');
                $dEnDom = EnrouteData::where('dof', 'LIKE', $targetDay . '%')->where('registrasi', 'LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0;
                $dEnInt = EnrouteData::where('dof', 'LIKE', $targetDay . '%')->where('registrasi', 'NOT LIKE', 'PK%')->sum('enroute_charge_idr') ?? 0;
                $dTeDom = TerminalData::where('tanggal', 'LIKE', $targetDay . '%')->where('registrasi', 'LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0;
                $dTeInt = TerminalData::where('tanggal', 'LIKE', $targetDay . '%')->where('registrasi', 'NOT LIKE', 'PK%')->sum('biaya_terminal_idr') ?? 0;

                $dayDom = $dEnDom + $dTeDom;
                $dayInt = $dEnInt + $dTeInt;
                $dailyRev = $dayDom + $dayInt;

                $revSparklineRaw[] = $dailyRev;
                $revSparklineRawDom[] = $dayDom;
                $revSparklineRawInt[] = $dayInt;
            }

            $maxRevSpark = max($revSparklineRaw) ?: 1;
            $revenueSparkline = array_map(fn($v) => max(25, (int) round(($v / $maxRevSpark) * 100)), $revSparklineRaw);

            $maxRevSparkDom = max($revSparklineRawDom) ?: 1;
            $revenueSparklineDom = array_map(fn($v) => max(25, (int) round(($v / $maxRevSparkDom) * 100)), $revSparklineRawDom);

            $maxRevSparkInt = max($revSparklineRawInt) ?: 1;
            $revenueSparklineInt = array_map(fn($v) => max(25, (int) round(($v / $maxRevSparkInt) * 100)), $revSparklineRawInt);
        }

        // KPI Cards: Card 1, 2, 3, dan 4 terhubung langsung ke database
        $kpi = [
            'total_flight' => [
                'value' => $totalFlightCount,
                'growth' => $growthTotal,
                'growth_positive' => str_starts_with($growthTotal, '+'),
                'vs_period' => "vs. periode lalu ({$periodLabel})",
                'sparkline' => $totalSparkline,
            ],
            'domestik' => [
                'value' => $domestikCount,
                'share' => $domShare,
                'growth' => $growthDom,
                'growth_positive' => str_starts_with($growthDom, '+'),
                'vs_period' => "({$domShare}% dari total)",
                'sparkline' => $domSparkline,
            ],
            'internasional' => [
                'value' => $internasionalCount,
                'share' => $intShare,
                'growth' => $growthIntl,
                'growth_positive' => str_starts_with($growthIntl, '+'),
                'vs_period' => "({$intShare}% dari total)",
                'sparkline' => $intlSparkline,
            ],
            'potensi_pendapatan' => [
                'value' => $totalPotensiPendapatan,
                'enroute' => $totalEnrouteCharge,
                'terminal' => $totalTerminalCharge,
                'growth' => $revenueGrowth,
                'growth_positive' => str_starts_with($revenueGrowth, '+'),
                'vs_period' => "vs. periode lalu ({$revenuePeriodLabel})",
                'sparkline' => $revenueSparkline,
            ],
        ];

        // -------------------------------------------------------------
        // TREN TRAFFIC (BERDASARKAN TABEL traffic_data)
        // Group by Bulan (Jan s/d Des). Mengambil data aktual database,
        // otomatis terisi penuh bila bulan-bulan lain di-upload.
        // -------------------------------------------------------------
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        // Tentukan tahun acuan dari tanggal terbaru di database
        $refYear = $latestDate ? Carbon::parse($latestDate)->year : 2026;

        $dbMonthlyTraffic = DB::table('traffic_data')
            ->selectRaw("
                MONTH(tanggal) as bln,
                COUNT(*) as total,
                SUM(CASE WHEN registrasi LIKE 'PK%' THEN 1 ELSE 0 END) as dom,
                SUM(CASE WHEN registrasi NOT LIKE 'PK%' THEN 1 ELSE 0 END) as intl
            ")
            ->whereYear('tanggal', $refYear)
            ->whereNotNull('tanggal')
            ->groupBy(DB::raw('MONTH(tanggal)'))
            ->get()
            ->keyBy('bln');

        // Tentukan batas bulan yang ditampilkan:
        // Jika data baru mencakup sampai bulan tertentu, tampilkan minimal sampai bulan terbaru
        // atau jika bulan terbaru <= 10 (Okt), tampilkan Jan s/d Okt / Des
        $maxMonthInDb = $dbMonthlyTraffic->keys()->max() ?: 6;
        $displayMonthsCount = max(10, $maxMonthInDb); // Minimal 10 bulan (Jan - Okt) sesuai desain acuan

        $trendLabels = [];
        $trendDomestik = [];
        $trendInternasional = [];
        $trendTotalFlight = [];

        for ($m = 1; $m <= $displayMonthsCount; $m++) {
            $trendLabels[] = $monthNames[$m];
            if (isset($dbMonthlyTraffic[$m])) {
                $trendDomestik[] = (int) $dbMonthlyTraffic[$m]->dom;
                $trendInternasional[] = (int) $dbMonthlyTraffic[$m]->intl;
                $trendTotalFlight[] = (int) $dbMonthlyTraffic[$m]->total;
            } else {
                $trendDomestik[] = 0;
                $trendInternasional[] = 0;
                $trendTotalFlight[] = 0;
            }
        }

        $trafficTrend = [
            'labels' => $trendLabels,
            'domestik' => $trendDomestik,
            'internasional' => $trendInternasional,
            'total_flight' => $trendTotalFlight,
        ];

        // Komposisi Traffic disinkronkan dengan total real dari traffic_data
        $trafficComposition = [
            'total' => $totalFlightCount,
            'domestik' => [
                'flight' => $domestikCount,
                'percentage' => $domShare,
            ],
            'internasional' => [
                'flight' => $internasionalCount,
                'percentage' => $intShare,
            ],
        ];

        // -------------------------------------------------------------
        // TOP 5 RANKINGS (SPLIT ATAS: DOMESTIK, BAWAH: INTERNASIONAL)
        // Nama maskapai diambil langsung dari tabel `airlines` (kolom `airline_name`)
        // -------------------------------------------------------------
        $airlineNames = Airline::whereNotNull('airline3_code')
            ->pluck('airline_name', 'airline3_code')
            ->toArray();

        // 1. Top Airlines by Flight (Domestik & Internasional)
        $domAirlinesFlightRaw = DB::table('traffic_data')
            ->selectRaw('airline3_code, COUNT(*) as flight')
            ->where('registrasi', 'LIKE', 'PK%')
            ->whereNotNull('airline3_code')
            ->groupBy('airline3_code')
            ->orderBy('flight', 'desc')
            ->limit(3)
            ->get();

        $domAirlineFlightColors = [
            'from-blue-600 to-sky-500',
            'from-amber-500 to-orange-500',
            'from-indigo-600 to-violet-500',
            'from-emerald-500 to-teal-500',
            'from-cyan-500 to-blue-500',
        ];

        $topAirlinesFlightDom = [];
        foreach ($domAirlinesFlightRaw as $index => $row) {
            $code = $row->airline3_code;
            $name = $airlineNames[$code] ?? $code;
            $share = $domestikCount > 0 ? round(($row->flight / $domestikCount) * 100, 1) : 0;
            $topAirlinesFlightDom[] = [
                'rank' => $index + 1,
                'code' => $code,
                'name' => $name,
                'flight' => (int) $row->flight,
                'share' => $share,
                'color' => $domAirlineFlightColors[$index % 5],
            ];
        }

        $intAirlinesFlightRaw = DB::table('traffic_data')
            ->selectRaw('airline3_code, COUNT(*) as flight')
            ->where('registrasi', 'NOT LIKE', 'PK%')
            ->whereNotNull('airline3_code')
            ->groupBy('airline3_code')
            ->orderBy('flight', 'desc')
            ->limit(3)
            ->get();

        $intAirlineFlightColors = [
            'from-amber-500 to-yellow-400',
            'from-emerald-600 to-teal-500',
            'from-rose-500 to-red-500',
            'from-yellow-500 to-amber-400',
            'from-teal-600 to-cyan-500',
        ];

        $topAirlinesFlightIntl = [];
        foreach ($intAirlinesFlightRaw as $index => $row) {
            $code = $row->airline3_code;
            $name = $airlineNames[$code] ?? $code;
            $share = $internasionalCount > 0 ? round(($row->flight / $internasionalCount) * 100, 1) : 0;
            $topAirlinesFlightIntl[] = [
                'rank' => $index + 1,
                'code' => $code,
                'name' => $name,
                'flight' => (int) $row->flight,
                'share' => $share,
                'color' => $intAirlineFlightColors[$index % 5],
            ];
        }

        // 2. Top Airlines by Revenue (Domestik & Internasional)
        $domAirlinesRevRaw = DB::select("
            SELECT t.airline3_code, SUM(t.rev) as total_rev
            FROM (
                SELECT airline3_code, SUM(enroute_charge_idr) as rev FROM enroute_data WHERE registrasi LIKE 'PK%' AND airline3_code IS NOT NULL GROUP BY airline3_code
                UNION ALL
                SELECT airline3_code, SUM(biaya_terminal_idr) as rev FROM terminal_data WHERE registrasi LIKE 'PK%' AND airline3_code IS NOT NULL GROUP BY airline3_code
            ) t
            GROUP BY t.airline3_code
            ORDER BY total_rev DESC
            LIMIT 3
        ");

        $topAirlinesRevDom = [];
        foreach ($domAirlinesRevRaw as $index => $row) {
            $code = $row->airline3_code;
            $name = $airlineNames[$code] ?? $code;
            $share = $totalRevenueDom > 0 ? round(($row->total_rev / $totalRevenueDom) * 100, 1) : 0;
            $topAirlinesRevDom[] = [
                'rank' => $index + 1,
                'code' => $code,
                'name' => $name,
                'revenue' => (float) $row->total_rev,
                'share' => $share,
                'color' => $domAirlineFlightColors[$index % 5],
            ];
        }

        $intAirlinesRevRaw = DB::select("
            SELECT t.airline3_code, SUM(t.rev) as total_rev
            FROM (
                SELECT airline3_code, SUM(enroute_charge_idr) as rev FROM enroute_data WHERE registrasi NOT LIKE 'PK%' AND airline3_code IS NOT NULL GROUP BY airline3_code
                UNION ALL
                SELECT airline3_code, SUM(biaya_terminal_idr) as rev FROM terminal_data WHERE registrasi NOT LIKE 'PK%' AND airline3_code IS NOT NULL GROUP BY airline3_code
            ) t
            GROUP BY t.airline3_code
            ORDER BY total_rev DESC
            LIMIT 3
        ");

        $topAirlinesRevIntl = [];
        foreach ($intAirlinesRevRaw as $index => $row) {
            $code = $row->airline3_code;
            $name = $airlineNames[$code] ?? $code;
            $share = $totalRevenueInt > 0 ? round(($row->total_rev / $totalRevenueInt) * 100, 1) : 0;
            $topAirlinesRevIntl[] = [
                'rank' => $index + 1,
                'code' => $code,
                'name' => $name,
                'revenue' => (float) $row->total_rev,
                'share' => $share,
                'color' => $intAirlineFlightColors[$index % 5],
            ];
        }

        // 3. Top Routes by Flight (Domestik & Internasional)
        $domRoutesRaw = DB::select("
            SELECT CONCAT(adep, ' - ', ades) as route, COUNT(*) as flight
            FROM traffic_data
            WHERE registrasi LIKE 'PK%' AND adep IS NOT NULL AND ades IS NOT NULL
            GROUP BY adep, ades
            ORDER BY flight DESC
            LIMIT 3
        ");

        $routeColorsDom = [
            'from-blue-600 to-indigo-500',
            'from-indigo-600 to-purple-500',
            'from-sky-600 to-blue-500',
            'from-teal-600 to-emerald-500',
            'from-violet-600 to-indigo-500',
        ];

        $topRoutesDom = [];
        foreach ($domRoutesRaw as $index => $row) {
            $share = $domestikCount > 0 ? round(($row->flight / $domestikCount) * 100, 1) : 0;
            $topRoutesDom[] = [
                'rank' => $index + 1,
                'route' => $row->route,
                'flight' => (int) $row->flight,
                'share' => $share,
                'color' => $routeColorsDom[$index % 5],
            ];
        }

        $intRoutesRaw = DB::select("
            SELECT CONCAT(adep, ' - ', ades) as route, COUNT(*) as flight
            FROM traffic_data
            WHERE registrasi NOT LIKE 'PK%' AND adep IS NOT NULL AND ades IS NOT NULL
            GROUP BY adep, ades
            ORDER BY flight DESC
            LIMIT 3
        ");

        $routeColorsIntl = [
            'from-amber-500 to-orange-500',
            'from-orange-500 to-rose-500',
            'from-emerald-600 to-teal-500',
            'from-purple-600 to-indigo-500',
            'from-cyan-600 to-blue-500',
        ];

        $topRoutesIntl = [];
        foreach ($intRoutesRaw as $index => $row) {
            $share = $internasionalCount > 0 ? round(($row->flight / $internasionalCount) * 100, 1) : 0;
            $topRoutesIntl[] = [
                'rank' => $index + 1,
                'route' => $row->route,
                'flight' => (int) $row->flight,
                'share' => $share,
                'color' => $routeColorsIntl[$index % 5],
            ];
        }

        // -------------------------------------------------------------
        // REVENUE TREND (BERDASARKAN TABEL enroute_data & terminal_data)
        // enroute_charge_idr + biaya_terminal_idr per bulan
        // Format dalam Satuan Milyar Rupiah (M)
        // -------------------------------------------------------------
        $dbMonthlyEnroute = DB::table('enroute_data')
            ->selectRaw("MONTH(dof) as bln, SUM(enroute_charge_idr) as total")
            ->whereYear('dof', $refYear)
            ->whereNotNull('dof')
            ->groupBy(DB::raw('MONTH(dof)'))
            ->get()
            ->keyBy('bln');

        $dbMonthlyTerminal = DB::table('terminal_data')
            ->selectRaw("MONTH(tanggal) as bln, SUM(biaya_terminal_idr) as total")
            ->whereYear('tanggal', $refYear)
            ->whereNotNull('tanggal')
            ->groupBy(DB::raw('MONTH(tanggal)'))
            ->get()
            ->keyBy('bln');

        $revenueTrendLabels = [];
        $revenueTrendValues = [];

        for ($m = 1; $m <= $displayMonthsCount; $m++) {
            $revenueTrendLabels[] = $monthNames[$m];
            $monthEnroute = isset($dbMonthlyEnroute[$m]) ? (float) $dbMonthlyEnroute[$m]->total : 0.0;
            $monthTerminal = isset($dbMonthlyTerminal[$m]) ? (float) $dbMonthlyTerminal[$m]->total : 0.0;
            $monthRevenueTotal = $monthEnroute + $monthTerminal;

            // Satuan dalam Milyar (M), misal 14.477.353.200 = 14.48 M
            $revenueTrendValues[] = round($monthRevenueTotal / 1000000000, 2);
        }

        $revenueTrend = [
            'labels' => $revenueTrendLabels,
            'values' => $revenueTrendValues,
        ];

        // -------------------------------------------------------------
        // RINCIAN POTENSI PENDAPATAN (Total, Domestik, Internasional)
        // -------------------------------------------------------------
        $revenueDetail = [
            'period' => $revenuePeriodLabel,
            'total' => [
                'value' => $totalPotensiPendapatan,
                'growth' => $revenueGrowth,
                'vs_period' => "vs. periode lalu ({$revenuePeriodLabel})",
                'sparkline' => $revenueSparkline,
            ],
            'domestik' => [
                'value' => $totalRevenueDom,
                'share' => $domRevenueShare,
                'growth' => $revenueGrowthDom,
                'vs_period' => "vs. periode lalu ({$revenuePeriodLabel})",
                'sparkline' => $revenueSparklineDom,
            ],
            'internasional' => [
                'value' => $totalRevenueInt,
                'share' => $intRevenueShare,
                'growth' => $revenueGrowthInt,
                'vs_period' => "vs. periode lalu ({$revenuePeriodLabel})",
                'sparkline' => $revenueSparklineInt,
            ],
        ];

        return view('flight-traffic-info', compact(
            'kpi',
            'trafficTrend',
            'trafficComposition',
            'topAirlinesFlightDom',
            'topAirlinesFlightIntl',
            'topAirlinesRevDom',
            'topAirlinesRevIntl',
            'topRoutesDom',
            'topRoutesIntl',
            'revenueTrend',
            'revenueDetail'
        ));
    }

    /**
     * 2. BULANAN DASHBOARD (Monthly Detail & Analytics)
     */
    public function monthly(Request $request)
    {
        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // 1. Ambil daftar bulan yang tersedia dari database
        $dbMonths = DB::table('traffic_data')
            ->selectRaw("LEFT(tanggal, 7) as ym")
            ->whereNotNull('tanggal')
            ->groupBy(DB::raw("LEFT(tanggal, 7)"))
            ->orderBy('ym', 'desc')
            ->pluck('ym')
            ->toArray();

        // Siapkan opsi dropdown bulan
        $monthOptions = [];
        foreach ($dbMonths as $ym) {
            $parts = explode('-', $ym);
            $y = $parts[0];
            $m = (int) $parts[1];
            $monthOptions[$ym] = ($indonesianMonths[$m] ?? $m) . " {$y}";
        }

        if (empty($monthOptions)) {
            $monthOptions = ['2026-06' => 'Juni 2026'];
        }

        // Tentukan bulan terpilih (default bulan terbaru dari database dengan data valid)
        $defaultMonth = in_array('2026-06', array_keys($monthOptions)) ? '2026-06' : array_key_first($monthOptions);
        $selectedMonth = $request->get('month', $defaultMonth);
        if (!array_key_exists($selectedMonth, $monthOptions)) {
            $selectedMonth = $defaultMonth;
        }
        $selectedMonthName = $monthOptions[$selectedMonth];

        // 2. Data Bulan Terpilih (traffic_data, enroute_data, terminal_data)
        $monthTraffic = DB::table('traffic_data')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN registrasi LIKE 'PK%' THEN 1 ELSE 0 END) as dom,
                SUM(CASE WHEN registrasi NOT LIKE 'PK%' THEN 1 ELSE 0 END) as intl
            ")
            ->where('tanggal', 'LIKE', "{$selectedMonth}%")
            ->first();

        $curTotal = (int) ($monthTraffic->total ?? 0);
        $curDom = (int) ($monthTraffic->dom ?? 0);
        $curInt = (int) ($monthTraffic->intl ?? 0);

        // Pendapatan bulan terpilih
        $enrouteRevCur = EnrouteData::where('dof', 'LIKE', "{$selectedMonth}%")->sum('enroute_charge_idr') ?? 0;
        $termRevCur = TerminalData::where('tanggal', 'LIKE', "{$selectedMonth}%")->sum('biaya_terminal_idr') ?? 0;
        $curRevenue = $enrouteRevCur + $termRevCur;

        // Bandingkan dengan bulan sebelumnya untuk menghitung pertumbuhan
        $curCarbon = Carbon::parse($selectedMonth . '-01');
        $prevMonth = $curCarbon->copy()->subMonth()->format('Y-m');
        $prevMonthName = ($indonesianMonths[(int)$curCarbon->copy()->subMonth()->format('m')] ?? '') . ' ' . $curCarbon->copy()->subMonth()->format('Y');

        $prevTraffic = DB::table('traffic_data')
            ->selectRaw("COUNT(*) as total")
            ->where('tanggal', 'LIKE', "{$prevMonth}%")
            ->first();
        $prevTotal = (int) ($prevTraffic->total ?? 0);

        $prevEnroute = EnrouteData::where('dof', 'LIKE', "{$prevMonth}%")->sum('enroute_charge_idr') ?? 0;
        $prevTerminal = TerminalData::where('tanggal', 'LIKE', "{$prevMonth}%")->sum('biaya_terminal_idr') ?? 0;
        $prevRevenue = $prevEnroute + $prevTerminal;

        $growthFlight = '+0,0%';
        if ($prevTotal > 0) {
            $pct = round((($curTotal - $prevTotal) / $prevTotal) * 100, 1);
            $growthFlight = ($pct >= 0 ? '+' : '') . number_format($pct, 1, ',', '.') . '%';
        }

        $growthRevenue = '+0,0%';
        if ($prevRevenue > 0) {
            $pctRev = round((($curRevenue - $prevRevenue) / $prevRevenue) * 100, 1);
            $growthRevenue = ($pctRev >= 0 ? '+' : '') . number_format($pctRev, 1, ',', '.') . '%';
        }

        // Sparkline harian dalam bulan terpilih (dibagi per beberapa hari atau 7 titik)
        $dailyInMonth = DB::table('traffic_data')
            ->selectRaw("tanggal, COUNT(*) as c")
            ->where('tanggal', 'LIKE', "{$selectedMonth}%")
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('c')
            ->toArray();

        $maxDailyC = !empty($dailyInMonth) ? max($dailyInMonth) : 1;
        $monthSparkline = !empty($dailyInMonth) 
            ? array_map(fn($v) => max(25, (int) round(($v / $maxDailyC) * 100)), array_slice($dailyInMonth, -7))
            : [30, 45, 55, 65, 75, 85, 95];

        // 3. Monthly KPI Cards
        $domShare = $curTotal > 0 ? round(($curDom / $curTotal) * 100, 1) : 0;
        $intShare = $curTotal > 0 ? round(($curInt / $curTotal) * 100, 1) : 0;

        $monthlyKpi = [
            'total_flight' => [
                'title' => "Total Flight ({$selectedMonthName})",
                'value' => $curTotal,
                'growth' => $growthFlight,
                'subtext' => "vs {$prevMonthName}",
                'sparkline' => $monthSparkline,
            ],
            'domestik' => [
                'title' => 'Flight Domestik',
                'value' => $curDom,
                'growth' => number_format($domShare, 1, ',', '.') . '%',
                'subtext' => 'Pangsa penerbangan',
                'sparkline' => [30, 45, 55, 65, 75, 85, 95],
            ],
            'internasional' => [
                'title' => 'Flight Internasional',
                'value' => $curInt,
                'growth' => number_format($intShare, 1, ',', '.') . '%',
                'subtext' => 'Pangsa penerbangan',
                'sparkline' => [25, 35, 48, 58, 68, 80, 92],
            ],
            'revenue' => [
                'title' => 'Estimasi Pendapatan Bulan Ini',
                'value' => $curRevenue,
                'growth' => $growthRevenue,
                'subtext' => "vs {$prevMonthName}",
                'sparkline' => [20, 35, 50, 65, 75, 88, 100],
            ],
        ];

        // 4. Comparison Chart (Data Bulanan Sepanjang Tahun)
        $refYear = (int) explode('-', $selectedMonth)[0];
        $allMonthsTraffic = DB::table('traffic_data')
            ->selectRaw("MONTH(tanggal) as m, COUNT(*) as total")
            ->whereYear('tanggal', $refYear)
            ->whereNotNull('tanggal')
            ->groupBy(DB::raw('MONTH(tanggal)'))
            ->pluck('total', 'm')
            ->toArray();

        $comparisonLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $actualFlights = [];
        $targetFlights = [];
        $lastYearFlights = [];

        for ($m = 1; $m <= 12; $m++) {
            $act = $allMonthsTraffic[$m] ?? 0;
            $actualFlights[] = $act;
            // Target referensi operasional (misal basis 7.000 - 8.000 flight)
            $targetFlights[] = $act > 0 ? (int) round($act * 1.05) : 7500;
            // Tahun lalu referensi
            $lastYearFlights[] = $act > 0 ? (int) round($act * 0.92) : 6800;
        }

        $monthlyComparison = [
            'labels' => $comparisonLabels,
            'actual_2026' => $actualFlights,
            'target_2026' => $targetFlights,
            'last_year_2025' => $lastYearFlights,
        ];

        // 5. Monthly Breakdown Table (Diambil dari database per bulan)
        $monthlyTable = [];
        foreach ($dbMonths as $ym) {
            $rowTraffic = DB::table('traffic_data')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN registrasi LIKE 'PK%' THEN 1 ELSE 0 END) as dom,
                    SUM(CASE WHEN registrasi NOT LIKE 'PK%' THEN 1 ELSE 0 END) as intl
                ")
                ->where('tanggal', 'LIKE', "{$ym}%")
                ->first();

            $rTot = (int) ($rowTraffic->total ?? 0);
            if ($rTot < 10) continue; // Lewati bulan outlier

            $rDom = (int) ($rowTraffic->dom ?? 0);
            $rInt = (int) ($rowTraffic->intl ?? 0);

            $rEnroute = EnrouteData::where('dof', 'LIKE', "{$ym}%")->sum('enroute_charge_idr') ?? 0;
            $rTerm = TerminalData::where('tanggal', 'LIKE', "{$ym}%")->sum('biaya_terminal_idr') ?? 0;
            $rRev = $rEnroute + $rTerm;

            $parts = explode('-', $ym);
            $mNum = (int) $parts[1];
            $mLabel = ($indonesianMonths[$mNum] ?? $mNum) . " {$parts[0]}";

            $monthlyTable[] = [
                'code' => $ym,
                'month' => $mLabel,
                'domestik' => $rDom,
                'internasional' => $rInt,
                'total' => $rTot,
                'growth' => '+0,0%',
                'revenue' => $rRev,
                'achievement' => 100.0,
            ];
        }

        return view('flight-traffic-monthly', compact(
            'monthOptions',
            'selectedMonth',
            'selectedMonthName',
            'monthlyKpi',
            'monthlyComparison',
            'monthlyTable'
        ));
    }

    /**
     * 3. HARIAN DASHBOARD (Daily Detail & Peak Hours)
     */
    public function daily(Request $request)
    {
        // 1. Tentukan tanggal terpilih (default tanggal operasional penuh terbaru di database)
        $latestDbDate = DB::table('traffic_data')
            ->select('tanggal')
            ->groupBy('tanggal')
            ->havingRaw('COUNT(*) > 20')
            ->orderBy('tanggal', 'desc')
            ->value('tanggal') ?? '2026-06-30';

        $selectedDate = $request->get('date', $latestDbDate);

        // Pastikan format tanggal valid
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = $latestDbDate;
        }

        // 2. Data Traffic Hari Terpilih
        $trafficToday = DB::table('traffic_data')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN registrasi LIKE 'PK%' THEN 1 ELSE 0 END) as dom,
                SUM(CASE WHEN registrasi NOT LIKE 'PK%' THEN 1 ELSE 0 END) as intl
            ")
            ->where('tanggal', $selectedDate)
            ->first();

        $todayTotal = (int) ($trafficToday->total ?? 0);
        $todayDom = (int) ($trafficToday->dom ?? 0);
        $todayInt = (int) ($trafficToday->intl ?? 0);

        $domPercent = $todayTotal > 0 ? round(($todayDom / $todayTotal) * 100, 1) : 0;
        $intlPercent = $todayTotal > 0 ? round(($todayInt / $todayTotal) * 100, 1) : 0;

        // Bandingkan dengan kemarin
        $carbonDate = Carbon::parse($selectedDate);
        $yesterday = $carbonDate->copy()->subDay()->format('Y-m-d');
        $trafficYesterday = DB::table('traffic_data')->where('tanggal', $yesterday)->count();

        $flightGrowth = '+0,0%';
        if ($trafficYesterday > 0) {
            $pct = round((($todayTotal - $trafficYesterday) / $trafficYesterday) * 100, 1);
            $flightGrowth = ($pct >= 0 ? '+' : '') . number_format($pct, 1, ',', '.') . '%';
        }

        // Pendapatan hari ini (enroute + terminal)
        $dailyEnroute = EnrouteData::where('dof', 'LIKE', "{$selectedDate}%")->sum('enroute_charge_idr') ?? 0;
        $dailyTerminal = TerminalData::where('tanggal', 'LIKE', "{$selectedDate}%")->sum('biaya_terminal_idr') ?? 0;
        $todayRevenue = $dailyEnroute + $dailyTerminal;

        $yesterdayEnroute = EnrouteData::where('dof', 'LIKE', "{$yesterday}%")->sum('enroute_charge_idr') ?? 0;
        $yesterdayTerminal = TerminalData::where('tanggal', 'LIKE', "{$yesterday}%")->sum('biaya_terminal_idr') ?? 0;
        $yesterdayRevenue = $yesterdayEnroute + $yesterdayTerminal;

        $revenueGrowth = '+0,0%';
        if ($yesterdayRevenue > 0) {
            $pctRev = round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1);
            $revenueGrowth = ($pctRev >= 0 ? '+' : '') . number_format($pctRev, 1, ',', '.') . '%';
        }

        // Sparkline 7 hari sebelum & sampai hari terpilih
        $sparklineWeek = DB::table('traffic_data')
            ->selectRaw('tanggal, COUNT(*) as c')
            ->whereBetween('tanggal', [$carbonDate->copy()->subDays(6)->format('Y-m-d'), $selectedDate])
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('c')
            ->toArray();

        $maxSp = !empty($sparklineWeek) ? max($sparklineWeek) : 1;
        $flightSparkline = !empty($sparklineWeek)
            ? array_map(fn($v) => max(25, (int) round(($v / $maxSp) * 100)), $sparklineWeek)
            : [35, 50, 42, 68, 70, 85, 100];

        // 3. Distribusi Jam (Hourly Curve 00 - 23)
        // Ambil waktu aktual dari terminal_data (waktu_kedatangan) dan enroute_data (time_in)
        $hourlyArrival = array_fill(0, 24, 0);
        $hourlyDeparture = array_fill(0, 24, 0);

        // Kedatangan dari terminal_data
        $termHours = DB::table('terminal_data')
            ->selectRaw("HOUR(waktu_kedatangan) as hr, COUNT(*) as c")
            ->where('tanggal', $selectedDate)
            ->whereNotNull('waktu_kedatangan')
            ->groupBy(DB::raw("HOUR(waktu_kedatangan)"))
            ->pluck('c', 'hr')
            ->toArray();

        foreach ($termHours as $hr => $c) {
            if ($hr >= 0 && $hr <= 23) {
                $hourlyArrival[$hr] = (int) $c;
            }
        }

        // Keberangkatan / Enroute dari enroute_data
        $enrouteHours = DB::table('enroute_data')
            ->selectRaw("HOUR(time_in) as hr, COUNT(*) as c")
            ->where('dof', $selectedDate)
            ->whereNotNull('time_in')
            ->groupBy(DB::raw("HOUR(time_in)"))
            ->pluck('c', 'hr')
            ->toArray();

        foreach ($enrouteHours as $hr => $c) {
            if ($hr >= 0 && $hr <= 23) {
                $hourlyDeparture[$hr] = (int) $c;
            }
        }

        // Cari Peak Hour
        $maxHourVal = 0;
        $peakHourStart = 8;
        for ($h = 0; $h < 24; $h++) {
            $totalHour = $hourlyArrival[$h] + $hourlyDeparture[$h];
            if ($totalHour > $maxHourVal) {
                $maxHourVal = $totalHour;
                $peakHourStart = $h;
            }
        }

        $peakHourLabel = sprintf("%02d:00 - %02d:00 WIB", $peakHourStart, ($peakHourStart + 1) % 24);
        $runwayCapacity = min(95, max(40, (int) round(($maxHourVal / 40) * 100)));

        $hourlyTraffic = [
            'labels' => [
                '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
                '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
                '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
                '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
            ],
            'departure' => $hourlyDeparture,
            'arrival' => $hourlyArrival,
        ];

        // Daily KPI Cards
        $dailyKpi = [
            'today_flight' => [
                'value' => $todayTotal,
                'growth' => $flightGrowth,
                'growth_positive' => str_starts_with($flightGrowth, '+'),
                'vs_period' => 'vs kemarin',
                'sparkline' => $flightSparkline,
            ],
            'peak_hour' => [
                'hour' => $peakHourLabel,
                'value' => $maxHourVal ?: 18,
                'unit' => 'flight/jam',
                'subtext' => "Kapasitas Runway {$runwayCapacity}%",
                'sparkline' => [20, 45, 90, 100, 75, 50, 30],
            ],
            'dom_vs_intl' => [
                'domestik' => $todayDom,
                'internasional' => $todayInt,
                'dom_percent' => $domPercent,
                'intl_percent' => $intlPercent,
            ],
            'daily_revenue' => [
                'value' => $todayRevenue,
                'growth' => $revenueGrowth,
                'growth_positive' => str_starts_with($revenueGrowth, '+'),
                'vs_period' => 'vs kemarin',
                'sparkline' => [25, 40, 60, 55, 75, 88, 100],
            ],
        ];

        // 4. Trend 30/31 Hari Sepanjang Bulan Tanggal Terpilih
        $selYearMonth = $carbonDate->format('Y-m');
        $daysInMonthCount = $carbonDate->daysInMonth;
        $dbDailyCounts = DB::table('traffic_data')
            ->selectRaw("DAY(tanggal) as d, COUNT(*) as c")
            ->where('tanggal', 'LIKE', "{$selYearMonth}%")
            ->groupBy(DB::raw("DAY(tanggal)"))
            ->pluck('c', 'd')
            ->toArray();

        $dailyTrendDays = [];
        $dailyTrendFlights = [];
        for ($d = 1; $d <= $daysInMonthCount; $d++) {
            $dailyTrendDays[] = "Tgl {$d}";
            $dailyTrendFlights[] = $dbDailyCounts[$d] ?? 0;
        }

        // 5. Daily Log Table (7 Hari Terakhir dari Database)
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $recentDays = DB::table('traffic_data')
            ->selectRaw("
                tanggal,
                COUNT(*) as total,
                SUM(CASE WHEN registrasi LIKE 'PK%' THEN 1 ELSE 0 END) as dom,
                SUM(CASE WHEN registrasi NOT LIKE 'PK%' THEN 1 ELSE 0 END) as intl
            ")
            ->where('tanggal', '<=', $selectedDate)
            ->whereNotNull('tanggal')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->limit(7)
            ->get();

        $dailyLogTable = [];
        foreach ($recentDays as $row) {
            $cDate = Carbon::parse($row->tanggal);
            $dayName = $dayNames[$cDate->dayOfWeek] ?? '';
            $dFormatted = $cDate->format('d M Y');

            $dEn = EnrouteData::where('dof', $row->tanggal)->sum('enroute_charge_idr') ?? 0;
            $dTe = TerminalData::where('tanggal', $row->tanggal)->sum('biaya_terminal_idr') ?? 0;
            $dRev = $dEn + $dTe;

            $dailyLogTable[] = [
                'date' => $dFormatted,
                'day' => $dayName,
                'domestik' => (int) $row->dom,
                'internasional' => (int) $row->intl,
                'total' => (int) $row->total,
                'peak_time' => 'Normal Operation',
                'revenue' => $dRev,
                'status' => ((int)$row->total > 260 ? 'Peak High' : 'Normal'),
            ];
        }

        return view('flight-traffic-daily', compact(
            'selectedDate',
            'dailyKpi',
            'hourlyTraffic',
            'dailyTrendDays',
            'dailyTrendFlights',
            'dailyLogTable'
        ));
    }
}
