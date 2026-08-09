<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\BudgetRealisasi;

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
    protected $description = 'Import SAP budget data from CSV files';

    /**
     * Execute the console command.
     */
    public function handle()
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
            if ($file->getExtension() !== 'csv' && $file->getExtension() !== 'txt') {
                continue;
            }

            $processedCount++;
            $filePath = $file->getRealPath();
            $fileName = $file->getFilename();
            $this->info("Processing file: {$fileName}");

            try {
                // Try to extract date from filename if possible, otherwise use today
                $reportDate = now()->format('Y-m-d');
                if (preg_match('/(\d{4}-\d{2}-\d{2})/', $fileName, $matches)) {
                    $reportDate = $matches[1];
                } elseif (preg_match('/(\d{4}\d{2}\d{2})/', $fileName, $matches)) {
                    $reportDate = \Carbon\Carbon::createFromFormat('Ymd', $matches[1])->format('Y-m-d');
                }

                $content = file_get_contents($filePath);
                
                // Remove UTF-8 BOM
                $bom = pack('H*','EFBBBF');
                $content = preg_replace("/^$bom/", '', $content);
                
                // Parse CSV
                // Use str_getcsv to handle quotes
                $lines = explode("\n", $content);
                $headerMapping = [];
                $headerParsed = false;

                // Expected Headers mapping keys
                $targetColumns = ['rkap', 'release_budget', 'commitment', 'total_consume', 'available_budget'];
                
                $buffer = [];
                $rowCount = 0;

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    $row = str_getcsv($line, ','); // assume comma separated
                    
                    if (!$headerParsed) {
                        // find indexes for needed columns
                        foreach ($row as $index => $colName) {
                            $colClean = strtolower(trim($colName));
                            if (str_contains($colClean, 'funds center') || str_contains($colClean, 'commitment item')) {
                                $headerMapping['funds_center'] = $index;
                            }
                            if ($colClean === 'rkap') $headerMapping['rkap'] = $index;
                            if (str_contains($colClean, 'release budget') && !str_contains($colClean, 'not release')) $headerMapping['release_budget'] = $index;
                            if ($colClean === 'commitment') $headerMapping['commitment'] = $index;
                            if (str_contains($colClean, 'total consume') && !str_contains($colClean, '%')) $headerMapping['total_consume'] = $index;
                            if (str_contains($colClean, 'available budget')) $headerMapping['available_budget'] = $index;
                        }
                        
                        if (isset($headerMapping['funds_center'])) {
                            $headerParsed = true;
                        }
                        continue;
                    }

                    if (!isset($headerMapping['funds_center'])) continue; // skip if no header

                    $firstCol = trim($row[$headerMapping['funds_center']] ?? '');
                    if (empty($firstCol)) continue;

                    // Parse first column
                    $code = '';
                    $name = '';
                    if (strpos($firstCol, '  ') !== false) {
                        $parts = explode('  ', $firstCol, 2);
                        $code = trim($parts[0]);
                        $name = trim($parts[1] ?? '');
                    } else {
                        $code = $firstCol;
                    }

                    // Parse numeric values
                    $values = [];
                    foreach ($targetColumns as $col) {
                        $idx = $headerMapping[$col] ?? -1;
                        if ($idx !== -1 && isset($row[$idx])) {
                            $valStr = trim(str_replace(['"', ' '], '', $row[$idx]));
                            // SAP sometimes puts negative sign at the end
                            if (str_ends_with($valStr, '-')) {
                                $valStr = '-' . rtrim($valStr, '-');
                            }
                            // Remove decimals if comma exists
                            if (strpos($valStr, ',') !== false) {
                                $valStr = explode(',', $valStr)[0];
                            }
                            $values[$col] = (int) $valStr;
                        } else {
                            $values[$col] = 0;
                        }
                    }

                    // Level determination
                    if (strtolower($firstCol) === 'funds center') {
                        // Grand Total
                        BudgetRealisasi::updateOrCreate([
                            'report_date' => $reportDate,
                            'branch_code' => null,
                            'item_code' => null,
                            'level' => 'total',
                        ], array_merge($values, [
                            'branch_name' => null,
                            'item_name' => 'Grand Total',
                        ]));
                        $rowCount++;
                        continue;
                    }

                    if (preg_match('/^\d/', $code)) {
                        // Starts with number -> Item
                        $buffer[] = [
                            'item_code' => $code,
                            'item_name' => $name,
                            'values' => $values,
                        ];
                    } elseif (preg_match('/^[A-Za-z]/', $code)) {
                        // Starts with letter (e.g. A) -> Cabang
                        $branchCode = $code;
                        $branchName = $name;

                        // Process buffered items
                        foreach ($buffer as $item) {
                            BudgetRealisasi::updateOrCreate([
                                'report_date' => $reportDate,
                                'branch_code' => $branchCode,
                                'item_code' => $item['item_code'],
                                'level' => 'item',
                            ], array_merge($item['values'], [
                                'branch_name' => $branchName,
                                'item_name' => $item['item_name'],
                            ]));
                            $rowCount++;
                        }
                        // Clear buffer
                        $buffer = [];

                        // Process branch subtotal
                        BudgetRealisasi::updateOrCreate([
                            'report_date' => $reportDate,
                            'branch_code' => $branchCode,
                            'item_code' => null,
                            'level' => 'cabang',
                        ], array_merge($values, [
                            'branch_name' => $branchName,
                            'item_name' => null,
                        ]));
                        $rowCount++;
                    }
                }

                // Move file to archive
                File::move($filePath, $archivePath . '/' . $fileName);
                $this->info("Successfully processed {$fileName}. Rows: {$rowCount}");
                $totalRows += $rowCount;
                $successFiles++;

            } catch (\Exception $e) {
                Log::error("Failed to parse SAP CSV file {$fileName}: " . $e->getMessage());
                $this->error("Failed to process {$fileName}");
                
                // Move file to failed
                File::move($filePath, $failedPath . '/' . $fileName);
                $failedFiles++;
            }
        }

        $this->info("Import completed. Files processed: {$processedCount}. Success: {$successFiles}. Failed: {$failedFiles}. Total rows: {$totalRows}");
        return 0;
    }
}
