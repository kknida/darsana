<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SapBotSetting;

class SapSettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'sapSystem' => SapBotSetting::get('sapSystem', 'PRD'),
            'sapClient' => SapBotSetting::get('sapClient', '100'),
            'sapUser' => SapBotSetting::get('sapUser', 'RPA_USER'),
            'sapLang' => SapBotSetting::get('sapLang', 'EN'),
            'exportFolder' => SapBotSetting::get('exportFolder', ''),
            'filePrefix' => SapBotSetting::get('filePrefix', 'realisasi_'),
            'reportTx' => SapBotSetting::get('reportTx', 'ZFM001'),
            'fmArea' => SapBotSetting::get('fmArea', '1000'),
            'fundCenterLow' => SapBotSetting::get('fundCenterLow', 'A022020000'),
            'fundCenterHigh' => SapBotSetting::get('fundCenterHigh', 'A022020005'),
            'logoutAfter' => SapBotSetting::get('logoutAfter', '1'),
        ];

        // Ambil scheduleTime
        $scheduleTime = SapBotSetting::get('scheduleTime', '18:00');
        $hour = '';
        $minute = '';
        if ($scheduleTime) {
            $parts = explode(':', $scheduleTime);
            if (count($parts) == 2) {
                $h = (int)$parts[0];
                $m = $parts[1];
                if ($h == 0) $h = 24;
                $hour = $h;
                $minute = $m;
            }
        }
        
        $botStatus = [
            'botLastSeen' => SapBotSetting::get('botLastSeen', ''),
            'botStatus' => SapBotSetting::get('botStatus', ''),
            'botMachineName' => SapBotSetting::get('botMachineName', ''),
            'botVersion' => SapBotSetting::get('botVersion', ''),
            'lastRunAt' => SapBotSetting::get('lastRunAt', ''),
            'lastRunStatus' => SapBotSetting::get('lastRunStatus', ''),
        ];

        $latestImport = \App\Models\ImportLog::where('status', 'success')->latest('created_at')->first();

        return view('admin.sap-settings.index', compact('settings', 'hour', 'minute', 'botStatus', 'latestImport'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'sapSystem' => 'sometimes|required|string',
            'sapClient' => 'sometimes|required|string',
            'sapUser' => 'sometimes|required|string',
            'sapLang' => 'sometimes|required|string',
            'exportFolder' => 'required|string',
            'filePrefix' => 'sometimes|required|string',
            'reportTx' => 'sometimes|required|string',
            'fmArea' => 'sometimes|required|string',
            'fundCenterLow' => 'sometimes|required|string',
            'fundCenterHigh' => 'sometimes|required|string',
            'hour' => 'required|numeric|min:1|max:24',
            'minute' => 'required|numeric|min:0|max:59',
        ], [
            'hour.required' => 'Jam wajib dipilih.',
            'minute.required' => 'Menit wajib dipilih.',
            'exportFolder.required' => 'Folder Export tidak boleh kosong.'
        ]);

        if ($request->has('sapSystem')) SapBotSetting::put('sapSystem', $request->sapSystem);
        if ($request->has('sapClient')) SapBotSetting::put('sapClient', $request->sapClient);
        if ($request->has('sapUser')) SapBotSetting::put('sapUser', $request->sapUser);
        if ($request->has('sapLang')) SapBotSetting::put('sapLang', $request->sapLang);
        SapBotSetting::put('exportFolder', $request->exportFolder);
        if ($request->has('filePrefix')) SapBotSetting::put('filePrefix', $request->filePrefix);
        if ($request->has('reportTx')) SapBotSetting::put('reportTx', $request->reportTx);
        if ($request->has('fmArea')) SapBotSetting::put('fmArea', $request->fmArea);
        if ($request->has('fundCenterLow')) SapBotSetting::put('fundCenterLow', $request->fundCenterLow);
        if ($request->has('fundCenterHigh')) SapBotSetting::put('fundCenterHigh', $request->fundCenterHigh);
        
        if ($request->has('logoutAfter')) {
            SapBotSetting::put('logoutAfter', '1');
        } elseif ($request->has('update_all')) {
            // If the form submitted everything but logoutAfter is absent, it means it's unchecked
            SapBotSetting::put('logoutAfter', '0');
        }

        if ($request->filled('sapPass')) {
            SapBotSetting::put('sapPass', $request->sapPass, true);
        }

        // Format hour dan minute
        $h = (int)$request->hour;
        $m = (int)$request->minute;
        if ($h == 24) $h = 0;
        $scheduleTime = sprintf('%02d:%02d', $h, $m);

        SapBotSetting::put('scheduleTime', $scheduleTime);

        return back()->with('success', 'Pengaturan Bot SAP berhasil disimpan. Jadwal eksekusi ditetapkan pukul ' . $scheduleTime . ' WIB.');
    }
}
