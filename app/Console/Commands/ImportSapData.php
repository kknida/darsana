<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\SapImportService;
use App\Services\Stream2ImportService;

class ImportSapData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SAP budget data and Stream2 Excel reports (Terminal, Enroute, Movement) from incoming folder';

    /**
     * Execute the console command.
     */
    public function handle(SapImportService $sapImportService, Stream2ImportService $stream2ImportService)
    {
        $importPath = storage_path('app/sap/incoming');
        $archivePath = storage_path('app/sap/archive');
        $failedPath = storage_path('app/sap/failed');

        if (!File::isDirectory($importPath)) {
            File::makeDirectory($importPath, 0755, true);
        }
        if (!File::isDirectory($archivePath)) {
            File::makeDirectory($archivePath, 0755, true);
        }
        if (!File::isDirectory($failedPath)) {
            File::makeDirectory($failedPath, 0755, true);
        }

        $files = File::files($importPath);
        $processedCount = 0;
        $totalRows = 0;
        $successFiles = 0;
        $failedFiles = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'])) {
                continue;
            }

            $processedCount++;
            $filePath = $file->getRealPath();
            $fileName = $file->getFilename();
            $lowerName = strtolower($fileName);
            $this->info("Processing file: {$fileName}");

            try {
                // Deteksi apakah file dari Stream2 (Terminal, Enroute, Movement) atau SAP Realisasi Anggaran (ZFM001)
                $isStream2 = str_contains($lowerName, 'terminal') ||
                             str_contains($lowerName, 'enroute')  ||
                             str_contains($lowerName, 'movement') ||
                             str_contains($lowerName, 'stream');

                if ($isStream2) {
                    $result = $stream2ImportService->import($filePath, $fileName, 'new_atlazbot');
                    $rowCount = $result['rows_imported'] ?? 0;
                    $this->info("Stream2 Import [{$result['type']}]: {$result['message']}");
                } else {
                    $stats = $sapImportService->import($filePath, $fileName, 'new_atlazbot');
                    $rowCount = $stats['rows_imported'] ?? 0;
                    $this->info("SAP ZFM001 Import: {$rowCount} rows imported.");
                }

                // Pindahkan file ke archive setelah sukses
                $targetArchive = $archivePath . '/' . $fileName;
                if (File::exists($targetArchive)) {
                    File::delete($targetArchive);
                }
                File::move($filePath, $targetArchive);

                $totalRows += $rowCount;
                $successFiles++;

            } catch (\Exception $e) {
                Log::error("Failed to parse incoming file {$fileName}: " . $e->getMessage());
                $this->error("Failed to process {$fileName}: " . $e->getMessage());

                // Move file to failed
                $targetFailed = $failedPath . '/' . $fileName;
                if (File::exists($targetFailed)) {
                    File::delete($targetFailed);
                }
                File::move($filePath, $targetFailed);
                $failedFiles++;
            }
        }

        $this->info("Import completed. Files processed: {$processedCount}. Success: {$successFiles}. Failed: {$failedFiles}. Total rows: {$totalRows}");
        return 0;
    }
}
