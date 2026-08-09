<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SapBotSetting;
use App\Services\SapImportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SapBotController extends Controller
{
    public function settings()
    {
        $refreshRequested = SapBotSetting::get('refreshRequested', '0') === '1';
        $scheduleTime = SapBotSetting::get('scheduleTime', '18:00');
        $lastRunAt = SapBotSetting::get('lastRunAt', '');
        
        $shouldRunNow = false;
        if ($refreshRequested) {
            $shouldRunNow = true;
        } else {
            $now = Carbon::now('Asia/Jakarta');
            $scheduled = Carbon::createFromFormat('H:i', $scheduleTime, 'Asia/Jakarta');
            
            $isPastSchedule = $now->greaterThanOrEqualTo($scheduled);
            $hasRunToday = false;
            
            if ($lastRunAt) {
                try {
                    $lastRunDate = Carbon::parse($lastRunAt)->timezone('Asia/Jakarta')->format('Y-m-d');
                    if ($lastRunDate === $now->format('Y-m-d')) {
                        $hasRunToday = true;
                    }
                } catch (\Exception $e) {}
            }
            
            if ($isPastSchedule && !$hasRunToday) {
                $shouldRunNow = true;
            }
        }

        return response()->json([
            'sapSystem' => SapBotSetting::get('sapSystem', 'PRD'),
            'sapClient' => SapBotSetting::get('sapClient', '100'),
            'sapUser' => SapBotSetting::get('sapUser', 'RPA_USER'),
            'sapLang' => SapBotSetting::get('sapLang', 'EN'),
            'logoutAfter' => SapBotSetting::get('logoutAfter', '1') === '1',
            'exportFolder' => SapBotSetting::get('exportFolder', ''),
            'filePrefix' => SapBotSetting::get('filePrefix', 'realisasi_'),
            'reportTx' => SapBotSetting::get('reportTx', 'ZFM001'),
            'fmArea' => SapBotSetting::get('fmArea', '1000'),
            'fundCenterLow' => SapBotSetting::get('fundCenterLow', 'A022020000'),
            'fundCenterHigh' => SapBotSetting::get('fundCenterHigh', 'A022020005'),
            'scheduleTime' => $scheduleTime,
            'refreshRequested' => $refreshRequested,
            'shouldRunNow' => $shouldRunNow,
            'serverTime' => Carbon::now('Asia/Jakarta')->toIso8601String(),
        ]);
    }

    public function credentials(Request $request)
    {
        Log::info("Bot mengakses kredensial dari IP: " . $request->ip());
        return response()->json([
            'sapUser' => SapBotSetting::get('sapUser', ''),
            'sapPass' => SapBotSetting::get('sapPass', '')
        ]);
    }

    public function import(Request $request, SapImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:20480'
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $incomingDir = storage_path('app/sap/incoming');
        $archiveDir = storage_path('app/sap/archive');
        $failedDir = storage_path('app/sap/failed');

        if (!is_dir($incomingDir)) @mkdir($incomingDir, 0755, true);
        if (!is_dir($archiveDir)) @mkdir($archiveDir, 0755, true);
        if (!is_dir($failedDir)) @mkdir($failedDir, 0755, true);

        $file->move($incomingDir, $fileName);
        $filePath = $incomingDir . '/' . $fileName;

        try {
            $stats = $importService->import($filePath, $fileName, 'sap_bot');
            @rename($filePath, $archiveDir . '/' . $fileName);
            
            SapBotSetting::put('lastRunAt', Carbon::now('Asia/Jakarta')->toIso8601String());
            SapBotSetting::put('lastRunStatus', 'success');
            SapBotSetting::put('refreshRequested', '0');

            return response()->json([
                'status' => 'success',
                'rows_imported' => $stats['rows_imported'] ?? 0,
                'failed_count' => $stats['failed_count'] ?? 0,
                'skipped_count' => $stats['skipped_count'] ?? 0,
                'duration_seconds' => $stats['duration_seconds'] ?? 0.0,
                'message' => 'Impor berhasil'
            ]);
        } catch (\Exception $e) {
            @rename($filePath, $failedDir . '/' . $fileName);
            
            SapBotSetting::put('lastRunAt', Carbon::now('Asia/Jakarta')->toIso8601String());
            SapBotSetting::put('lastRunStatus', 'failed');
            SapBotSetting::put('refreshRequested', '0');

            Log::error("API Import Error: " . $e->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function heartbeat(Request $request)
    {
        $data = $request->validate([
            'status' => 'required|in:idle,running,error',
            'message' => 'nullable|string',
            'machineName' => 'required|string',
            'botVersion' => 'required|string'
        ]);

        $oldMachine = SapBotSetting::get('botMachineName', '');
        if ($oldMachine && $oldMachine !== $data['machineName']) {
            Log::warning("Bot SAP Machine Name berubah dari {$oldMachine} menjadi {$data['machineName']}");
        }

        SapBotSetting::put('botLastSeen', Carbon::now('Asia/Jakarta')->toIso8601String());
        SapBotSetting::put('botStatus', $data['status']);
        SapBotSetting::put('botMachineName', $data['machineName']);
        SapBotSetting::put('botVersion', $data['botVersion']);
        
        if (!empty($data['message'])) {
            Log::info("Bot Heartbeat Message: " . $data['message']);
        }

        return response()->json(['status' => 'ok']);
    }
}
