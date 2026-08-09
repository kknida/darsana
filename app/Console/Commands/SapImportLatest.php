<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Services\SapImportService;

class SapImportLatest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:import-latest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengimpor file CSV realisasi anggaran terbaru dari folder export SAP ke database';

    /**
     * Execute the console command.
     */
    public function handle(SapImportService $importService)
    {
        $exportDir = storage_path('app/sap/incoming');
        $archiveDir = storage_path('app/sap/archive');
        
        $newFile = $this->findNewestExportFile($exportDir);
        
        if (!$newFile) {
            $this->info("Tidak ada file CSV di folder");
            return Command::FAILURE;
        }

        $filePath = $newFile->getRealPath();
        $fileName = $newFile->getFilename();

        try {
            $stats = $importService->import($filePath, $fileName, 'schedule');
            
            if (!$this->safeCopyFile($filePath, $archiveDir . '/' . $fileName)) {
                Log::warning("sap:import-latest gagal menyalin file ke archive: {$fileName}");
            }
            
            $this->info("Impor sukses: {$stats['rows_imported']} baris, {$stats['branches_count']} cabang dari {$fileName}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("sap:import-latest error: " . $e->getMessage() . " @ " . $e->getFile() . ":" . $e->getLine());
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function findNewestExportFile(string $dir): ?\SplFileInfo
    {
        if (!File::isDirectory($dir)) {
            return null;
        }

        $candidates = collect(File::files($dir))
            ->filter(function ($file) {
                $ext = strtolower($file->getExtension());
                return in_array($ext, ['csv', 'txt', 'xlsx', 'xls']);
            })
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            });

        return $candidates->first();
    }

    private function safeCopyFile(string $from, string $to): bool
    {
        $dir = dirname($to);
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        for ($i = 0; $i < 5; $i++) {
            if (@copy($from, $to)) return true;
            usleep(500000); // tunggu 0.5 detik
        }
        return false;
    }
}
