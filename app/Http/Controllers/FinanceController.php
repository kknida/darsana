<?php

namespace App\Http\Controllers;

use App\Models\BudgetRealisasi;
use App\Services\SapImportService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class FinanceController extends Controller
{
    public function index()
    {
        // Get the latest date
        $latestDate = BudgetRealisasi::max('report_date');

        // Collect branches (level = cabang) for the latest date
        $branches = BudgetRealisasi::where('level', 'cabang')
            ->when($latestDate, function($q) use ($latestDate) {
                return $q->where('report_date', $latestDate);
            })
            ->orderBy('id')
            ->get();

        // Collect items
        $items = BudgetRealisasi::where('level', 'item')
            ->when($latestDate, function($q) use ($latestDate) {
                return $q->where('report_date', $latestDate);
            })
            ->get()
            ->groupBy('branch_code');

        $financeData = [];
        foreach ($branches as $branch) {
            $branchName = $branch->branch_name ?: $branch->branch_code;
            
            $financeData[$branchName] = [
                'rkap'       => (float) $branch->rkap,
                'release'    => (float) $branch->release_budget,
                'commitment' => (float) $branch->commitment,
                'consume'    => (float) $branch->total_consume,
                'available'  => (float) $branch->available_budget,
                'items'      => [],
            ];
            
            if (isset($items[$branch->branch_code])) {
                foreach ($items[$branch->branch_code] as $item) {
                    $financeData[$branchName]['items'][] = [
                        'code'       => $item->item_code,
                        'name'       => $item->item_name,
                        'rkap'       => (float) $item->rkap,
                        'release'    => (float) $item->release_budget,
                        'commitment' => (float) $item->commitment,
                        'consume'    => (float) $item->total_consume,
                        'available'  => (float) $item->available_budget,
                    ];
                }
            }
        }

        // Summary Data (use Total if exists, else sum branches)
        $grandTotal = BudgetRealisasi::where('level', 'total')
            ->when($latestDate, function($q) use ($latestDate) {
                return $q->where('report_date', $latestDate);
            })->first();

        if ($grandTotal) {
            $sumRkap = $grandTotal->rkap;
            $sumRelease = $grandTotal->release_budget;
            $sumCommit = $grandTotal->commitment;
            $sumConsume = $grandTotal->total_consume;
            $sumAvail = $grandTotal->available_budget;
        } else {
            $sumRkap = $branches->sum('rkap');
            $sumRelease = $branches->sum('release_budget');
            $sumCommit = $branches->sum('commitment');
            $sumConsume = $branches->sum('total_consume');
            $sumAvail = $branches->sum('available_budget');
        }
        
        $totalCabang = $branches->count();

        // Pass standard variables
        $activeFinanceFiles = \App\Models\FinanceUpload::latest()->pluck('file_name')->toArray();
        $financeUpdatedAt = $latestDate ? \Carbon\Carbon::parse($latestDate)->format('d M Y') : null;

        $financeLog = \App\Models\ImportLog::latest('created_at')->first();
        $financeLastUpdateTime = $financeLog ? $financeLog->created_at->setTimezone('Asia/Jakarta')->format('H:i \W\I\B') : '-';

        return response()->view('finance', compact(
            'financeData', 
            'activeFinanceFiles', 
            'financeUpdatedAt',
            'financeLog',
            'financeLastUpdateTime',
            'sumRkap', 'sumRelease', 'sumCommit', 'sumConsume', 'sumAvail',
            'totalCabang'
        ))
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
    }

    public function import(Request $request, SapImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            $stats = $importService->import(
                $request->file('file')->getRealPath(), 
                $request->file('file')->getClientOriginalName()
            );
            $count = BudgetRealisasi::where('report_date', now()->format('Y-m-d'))->count();
            
            return redirect()->route('finance')->with('success', "{$count} baris Realisasi Anggaran berhasil diimpor dan langsung tampil di tab Finance.");
        } catch (\Exception $e) {
            return redirect()->route('finance')->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function refresh()
    {
        \App\Models\SapBotSetting::put('refreshRequested', true);
        
        $msg = 'Permintaan terkirim. Bot akan menjalankan export pada pengecekan berikutnya, paling lama 1 menit lagi.';
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        
        return back()->with('success', $msg);
    }

    /**
     * Tunggu sampai ukuran file stabil (tidak berubah dalam 500ms).
     * Maksimal 3 kali percobaan. Tujuannya memastikan SAP sudah
     * selesai menulis file sebelum kita baca.
     */
    private function waitForFileStable(string $filePath, int $maxRetries = 3): void
    {
        for ($i = 0; $i < $maxRetries; $i++) {
            $size1 = filesize($filePath);
            usleep(500_000); // 500ms
            clearstatcache(true, $filePath);
            $size2 = filesize($filePath);

            if ($size1 === $size2) {
                return; // stabil
            }

            Log::info("SAP Refresh: File masih ditulis (size {$size1} -> {$size2}), menunggu...");
        }
    }

    // 
}
