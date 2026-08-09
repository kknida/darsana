<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SapBotSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'sapSystem' => 'PRD',
            'sapClient' => '100',
            'sapUser' => 'RPA_USER',
            'sapPass' => 'sapPass123', // Akan dienkripsi
            'sapLang' => 'EN',
            'logoutAfter' => '1',
            'exportFolder' => '',
            'filePrefix' => 'realisasi_',
            'reportTx' => 'ZFM001',
            'fmArea' => '1000',
            'fundCenterLow' => 'A022020000',
            'fundCenterHigh' => 'A022020005',
            'scheduleTime' => '18:00',
            'refreshRequested' => '0',
            'lastRunAt' => '',
            'lastRunStatus' => '',
            'botLastSeen' => '',
            'botStatus' => '',
            'botMachineName' => '',
            'botVersion' => '',
        ];

        foreach ($settings as $key => $value) {
            $isEncrypted = ($key === 'sapPass');
            
            $existing = \App\Models\SapBotSetting::where('key', $key)->first();
            if (!$existing) {
                \App\Models\SapBotSetting::put($key, $value, $isEncrypted);
            }
        }
    }
}
