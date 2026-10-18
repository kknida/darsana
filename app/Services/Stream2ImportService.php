<?php

namespace App\Services;

use App\Models\Airline;
use App\Models\EnrouteData;
use App\Models\EnrouteUpload;
use App\Models\TerminalData;
use App\Models\TerminalUpload;
use App\Models\TrafficData;
use App\Models\TrafficUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Stream2ImportService
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Deteksi tipe file Stream2 dan proses ke database
     *
     * @return array [ 'type' => 'terminal|enroute|movement', 'rows_imported' => int, 'message' => string ]
     */
    public function import(string $filePath, string $originalFileName, string $source = 'new_atlazbot'): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();
        unset($worksheet, $spreadsheet);

        if (empty($rows) || count($rows) < 2) {
            throw new \Exception("File {$originalFileName} kosong atau tidak memiliki data.");
        }

        // Cari baris header di 10 baris pertama
        $headerRowIndex = 0;
        $headers = [];
        $type = $this->detectReportType($rows, $originalFileName, $headerRowIndex, $headers);

        if (!$type) {
            throw new \Exception("Tipe laporan Stream2 tidak dikenali untuk file: {$originalFileName}");
        }

        // Ambil data dimulai setelah baris header
        $dataRows = array_slice($rows, $headerRowIndex + 1);

        return match ($type) {
            'terminal' => $this->importTerminal($dataRows, $headers, $originalFileName, $source),
            'enroute'  => $this->importEnroute($dataRows, $headers, $originalFileName, $source),
            'movement' => $this->importMovement($dataRows, $headers, $originalFileName, $source),
            default    => throw new \Exception("Tipe import {$type} belum didukung.")
        };
    }

    /**
     * Deteksi jenis report dari nama file atau baris header
     */
    private function detectReportType(array $rows, string $fileName, int &$headerRowIndex, array &$headers): ?string
    {
        $lowerName = strtolower($fileName);

        // Scan header baris 0-10 untuk baris kolom data (bukan baris judul dokumen)
        for ($i = 0; $i < min(15, count($rows)); $i++) {
            $rowStrings = array_map(fn($v) => strtolower(trim((string)$v)), $rows[$i]);
            $rowText = implode(' ', $rowStrings);

            // Deteksi Enroute Charge: harus memiliki 'point in' atau ('aircraft id'/'acid' dan 'route charge')
            if (str_contains($rowText, 'point in') || (str_contains($rowText, 'aircraft id') && str_contains($rowText, 'route charge'))) {
                $headerRowIndex = $i;
                $headers = $rowStrings;
                return 'enroute';
            }

            // Deteksi Terminal Charge: harus memiliki 'mtow' atau ('aircraft id'/'acid' dan 'terminal charge')
            if (str_contains($rowText, 'mtow') || (str_contains($rowText, 'aircraft id') && str_contains($rowText, 'terminal charge'))) {
                $headerRowIndex = $i;
                $headers = $rowStrings;
                return 'terminal';
            }

            // Deteksi Movement / Traffic: ada 'pushback', 'taxi', 'ruid dep', 'riu dep', 'dep/arr/local'
            if (str_contains($rowText, 'pushback') || str_contains($rowText, 'taxi') || str_contains($rowText, 'riu dep') || str_contains($rowText, 'ruid dep')) {
                $headerRowIndex = $i;
                $headers = $rowStrings;
                return 'movement';
            }
        }

        // Fallback dari nama file jika tidak terdeteksi dari isi
        if (str_contains($lowerName, 'terminal')) {
            $headerRowIndex = 1;
            $headers = array_map(fn($v) => strtolower(trim((string)$v)), $rows[1] ?? []);
            return 'terminal';
        }
        if (str_contains($lowerName, 'enroute') || str_contains($lowerName, 'enc')) {
            $headerRowIndex = 1;
            $headers = array_map(fn($v) => strtolower(trim((string)$v)), $rows[1] ?? []);
            return 'enroute';
        }
        if (str_contains($lowerName, 'movement') || str_contains($lowerName, 'mov')) {
            $headerRowIndex = 6;
            $headers = array_map(fn($v) => strtolower(trim((string)$v)), $rows[6] ?? []);
            return 'movement';
        }

        return null;
    }

    /**
     * 1. Import Data Terminal Charge
     */
    private function importTerminal(array $rows, array $headers, string $fileName, string $source): array
    {
        $col = $this->mapColumns($headers, [
            'aircraft_id'        => ['aircraft id', 'acid', 'aircraft'],
            'adep'               => ['adep', 'from', 'asal'],
            'ades'               => ['ades', 'to', 'tujuan'],
            'dof'                => ['dof', 'tanggal', 'date'],
            'registrasi'         => ['registrasi', 'reg', 'a-reg'],
            'type'               => ['type', 'tipe', 'a-type'],
            'ata'                => ['ata', 'waktu kedatangan', 'arrival time'],
            'mtow'               => ['mtow'],
            'terminal_charge'    => ['terminal charge', 'biaya terminal', 'charge'],
            'flight_type'        => ['flight type', 'jenis penerbangan']
        ]);

        return DB::transaction(function () use ($rows, $col, $fileName, $source) {
            $upload = TerminalUpload::create([
                'file_name'   => $fileName,
                'uploaded_by' => $source,
                'status'      => 'pending',
                'total_rows'  => 0,
            ]);

            $inserted = 0;
            $dates = [];
            $batch = [];
            $now = now();

            foreach ($rows as $row) {
                $acid = trim((string)($row[$col['aircraft_id']] ?? ''));
                if (empty($acid) || strtolower($acid) === 'total' || str_contains(strtolower($acid), 'total')) continue;

                $dofRaw = $row[$col['dof']] ?? null;
                $date = $this->parseDate($dofRaw) ?? now()->toDateString();
                $dates[] = $date;

                $rawCharge = $row[$col['terminal_charge']] ?? 0;
                $charge = $this->parseMoney($rawCharge);
                $currency = $this->detectCurrency($rawCharge);

                $airline3 = substr($acid, 0, 3);
                $airline = Airline::where('airline3_code', $airline3)->first();

                // Hitung IDR jika currency USD
                $rate = null;
                $chargeIdr = $charge;
                if ($currency === 'USD') {
                    $rate = $this->currencyService->getUSDRate($date) ?? 16000;
                    $chargeIdr = $charge * $rate;
                }

                $batch[] = [
                    'id_terminal_upload'  => $upload->id_terminal_upload,
                    'aircraft_id'         => $acid,
                    'airline3_code'       => $airline3,
                    'id_airline'          => $airline?->id,
                    'bandara'             => trim((string)($row[$col['ades']] ?? 'WARR')),
                    'tanggal'             => $date,
                    'registrasi'          => trim((string)($row[$col['registrasi']] ?? '')),
                    'type'                => trim((string)($row[$col['type']] ?? '')),
                    'terminal'            => '1',
                    'waktu_kedatangan'    => $this->parseTime($row[$col['ata']] ?? null),
                    'waktu_keberangkatan' => null,
                    'gate'                => null,
                    'parking_stand'       => null,
                    'biaya_terminal'      => $charge,
                    'currency'            => $currency,
                    'biaya_terminal_idr'  => $chargeIdr,
                    'exchange_rate'       => $rate,
                    'status_penerbangan'  => trim((string)($row[$col['flight_type']] ?? '')),
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];

                if (count($batch) >= 200) {
                    TerminalData::insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                TerminalData::insert($batch);
                $inserted += count($batch);
            }

            $upload->update([
                'tanggal_awal'  => !empty($dates) ? min($dates) : null,
                'tanggal_akhir' => !empty($dates) ? max($dates) : null,
                'status'        => 'processed',
                'total_rows'    => $inserted,
            ]);

            return [
                'type'          => 'terminal',
                'rows_imported' => $inserted,
                'message'       => "Berhasil import {$inserted} data Terminal Charge.",
            ];
        });
    }

    /**
     * 2. Import Data Enroute Charge
     */
    private function importEnroute(array $rows, array $headers, string $fileName, string $source): array
    {
        $col = $this->mapColumns($headers, [
            'aircraft_id'   => ['aircraft id', 'aircraft', 'acid'],
            'adep'          => ['adep', 'from', 'asal'],
            'ades'          => ['ades', 'to', 'tujuan'],
            'dof'           => ['dof', 'tanggal', 'date'],
            'registrasi'    => ['registrasi', 'reg', 'a-reg'],
            'type'          => ['type', 'tipe', 'a-type'],
            'point_in'      => ['point in', 'entry'],
            'time_in'       => ['time in', 'entry time'],
            'point_out'     => ['point out', 'exit'],
            'time_out'      => ['time out', 'exit time'],
            'faktor_jarak'  => ['faktor jarak', 'distance factor'],
            'faktor_berat'  => ['faktor berat', 'weight factor'],
            'route_unit'    => ['route unit', 'unit'],
            'route_charge'  => ['route charge', 'enroute charge', 'charge'],
            'flight_type'   => ['flight type', 'jenis penerbangan']
        ]);

        return DB::transaction(function () use ($rows, $col, $fileName, $source) {
            $upload = EnrouteUpload::create([
                'file_name'   => $fileName,
                'uploaded_by' => $source,
                'status'      => 'pending',
                'total_rows'  => 0,
            ]);

            $inserted = 0;
            $dates = [];
            $batch = [];
            $now = now();

            foreach ($rows as $row) {
                $acid = trim((string)($row[$col['aircraft_id']] ?? ''));
                if (empty($acid) || strtolower($acid) === 'total') continue;

                $dofRaw = $row[$col['dof']] ?? null;
                $date = $this->parseDate($dofRaw) ?? now()->toDateString();
                $dates[] = $date;

                $rawCharge = $row[$col['route_charge']] ?? 0;
                $charge = $this->parseMoney($rawCharge);
                $currency = $this->detectCurrency($rawCharge);

                $airline3 = substr($acid, 0, 3);
                $airline = Airline::where('airline3_code', $airline3)->first();

                $rate = null;
                $chargeIdr = $charge;
                if ($currency === 'USD') {
                    $rate = $this->currencyService->getUSDRate($date) ?? 16000;
                    $chargeIdr = $charge * $rate;
                }

                $batch[] = [
                    'id_enroute_upload'  => $upload->id_enroute_upload,
                    'aircraft_id'        => $acid,
                    'airline3_code'      => $airline3,
                    'id_airline'         => $airline?->id,
                    'adep'               => trim((string)($row[$col['adep']] ?? '')),
                    'ades'               => trim((string)($row[$col['ades']] ?? '')),
                    'dof'                => $date,
                    'registrasi'         => trim((string)($row[$col['registrasi']] ?? '')),
                    'type'               => trim((string)($row[$col['type']] ?? '')),
                    'point_in'           => trim((string)($row[$col['point_in']] ?? '')),
                    'time_in'            => $this->parseTime($row[$col['time_in']] ?? null),
                    'point_out'          => trim((string)($row[$col['point_out']] ?? '')),
                    'time_out'           => $this->parseTime($row[$col['time_out']] ?? null) ?? '00:00:00',
                    'faktor_jarak'       => $this->parseDecimal($row[$col['faktor_jarak']] ?? null),
                    'faktor_berat'       => (int)($this->parseDecimal($row[$col['faktor_berat']] ?? null)),
                    'route_unit'         => $this->parseDecimal($row[$col['route_unit']] ?? null),
                    'enroute_charge'     => $charge,
                    'currency'           => $currency,
                    'exchange_rate'      => $rate,
                    'enroute_charge_idr' => $chargeIdr,
                    'flight_type'        => trim((string)($row[$col['flight_type']] ?? '')),
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];

                if (count($batch) >= 200) {
                    EnrouteData::insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                EnrouteData::insert($batch);
                $inserted += count($batch);
            }

            $upload->update([
                'tanggal_awal'  => !empty($dates) ? min($dates) : null,
                'tanggal_akhir' => !empty($dates) ? max($dates) : null,
                'status'        => 'processed',
                'total_rows'    => $inserted,
            ]);

            return [
                'type'          => 'enroute',
                'rows_imported' => $inserted,
                'message'       => "Berhasil import {$inserted} data Enroute Charge.",
            ];
        });
    }

    /**
     * 3. Import Data Movement / Traffic
     */
    private function importMovement(array $rows, array $headers, string $fileName, string $source): array
    {
        $col = $this->mapColumns($headers, [
            'tanggal'      => ['tanggal', 'date', 'dof'],
            'aircraft_id'  => ['acid', 'aircraft id', 'flight no', 'callsign'],
            'registrasi'   => ['a-reg', 'registrasi', 'reg'],
            'type'         => ['a-type', 'type', 'tipe'],
            'adep'         => ['adep', 'from', 'asal'],
            'ades'         => ['ades', 'to', 'tujuan'],
            'eobt'         => ['eobt'],
            'pushback'     => ['pushback'],
            'taxi'         => ['taxi'],
            'dep_arr_lcl'  => ['dep/arr/local', 'dep_arr_lcl', 'movement type', 'd/a'],
            'atd'          => ['atd'],
            'eta'          => ['eta'],
            'ata'          => ['ata'],
            'ruid_dep'     => ['riu dep', 'ruid dep'],
            'rui_arr'      => ['riu arr', 'rui arr'],
            'parking_dep'  => ['parking dep'],
            'parking_arr'  => ['parking arr'],
            'pob'          => ['pob'],
            'remark'       => ['remark'],
            'status_flight'=> ['status flight', 'status']
        ]);

        return DB::transaction(function () use ($rows, $col, $fileName, $source) {
            $upload = TrafficUpload::create([
                'file_name'   => $fileName,
                'uploaded_by' => $source,
                'status'      => 'pending',
                'total_rows'  => 0,
            ]);

            $inserted = 0;
            $dates = [];
            $batch = [];
            $now = now();

            foreach ($rows as $row) {
                $acid = trim((string)($row[$col['aircraft_id']] ?? ''));
                if (empty($acid) || strtolower($acid) === 'total') continue;

                $tglRaw = $row[$col['tanggal']] ?? null;
                $date = $this->parseDate($tglRaw) ?? now()->toDateString();
                $dates[] = $date;

                $airline3 = substr($acid, 0, 3);
                $airline = Airline::where('airline3_code', $airline3)->first();

                $batch[] = [
                    'id_traffic_upload' => $upload->id_traffic_upload,
                    'tanggal'           => $date,
                    'aircraft_id'       => $acid,
                    'airline3_code'     => $airline3,
                    'id_airline'        => $airline?->id,
                    'registrasi'        => trim((string)($row[$col['registrasi']] ?? '')),
                    'type'              => trim((string)($row[$col['type']] ?? '')),
                    'adep'              => trim((string)($row[$col['adep']] ?? '')),
                    'ades'              => trim((string)($row[$col['ades']] ?? '')),
                    'eobt'              => trim((string)($row[$col['eobt']] ?? '')),
                    'pushback'          => trim((string)($row[$col['pushback']] ?? '')),
                    'taxi'              => trim((string)($row[$col['taxi']] ?? '')),
                    'dep_arr_lcl'       => trim((string)($row[$col['dep_arr_lcl']] ?? '')),
                    'atd'               => trim((string)($row[$col['atd']] ?? '')),
                    'eta'               => trim((string)($row[$col['eta']] ?? '')),
                    'ata'               => trim((string)($row[$col['ata']] ?? '')),
                    'ruid_dep'          => trim((string)($row[$col['ruid_dep']] ?? '')),
                    'rui_arr'           => trim((string)($row[$col['rui_arr']] ?? '')),
                    'parking_dep'       => trim((string)($row[$col['parking_dep']] ?? '')),
                    'parking_arr'       => trim((string)($row[$col['parking_arr']] ?? '')),
                    'pob'               => trim((string)($row[$col['pob']] ?? '')),
                    'remark'            => trim((string)($row[$col['remark']] ?? '')),
                    'status_flight'     => trim((string)($row[$col['status_flight']] ?? '')),
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                if (count($batch) >= 200) {
                    TrafficData::insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                TrafficData::insert($batch);
                $inserted += count($batch);
            }

            $upload->update([
                'tanggal_awal'  => !empty($dates) ? min($dates) : null,
                'tanggal_akhir' => !empty($dates) ? max($dates) : null,
                'status'        => 'processed',
                'total_rows'    => $inserted,
            ]);

            return [
                'type'          => 'movement',
                'rows_imported' => $inserted,
                'message'       => "Berhasil import {$inserted} data Movement Traffic.",
            ];
        });
    }

    /**
     * Helper: Mapping kolom dari array header
     */
    private function mapColumns(array $headers, array $fieldKeywords): array
    {
        $mapping = [];
        foreach ($fieldKeywords as $field => $keywords) {
            $mapping[$field] = -1;

            // 1. Coba exact match terlebih dahulu
            foreach ($headers as $idx => $headerText) {
                $clean = strtolower(trim((string)$headerText));
                foreach ($keywords as $kw) {
                    if ($clean === $kw) {
                        $mapping[$field] = $idx;
                        break 2;
                    }
                }
            }

            // 2. Jika tidak ada exact match, coba partial contains
            if ($mapping[$field] === -1) {
                foreach ($headers as $idx => $headerText) {
                    $clean = strtolower(trim((string)$headerText));
                    foreach ($keywords as $kw) {
                        if (str_contains($clean, $kw)) {
                            $mapping[$field] = $idx;
                            break 2;
                        }
                    }
                }
            }
        }
        return $mapping;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) return null;
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {}
        }
        $val = trim((string)$value);
        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTime($value): ?string
    {
        if (empty($value)) return null;
        if (is_numeric($value) && $value < 1) { // Excel time fraction
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('H:i:s');
            } catch (\Exception $e) {}
        }
        $val = trim((string)$value);
        if (preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $val)) {
            return strlen($val) === 5 ? $val . ':00' : $val;
        }
        return null;
    }

    private function parseMoney($value): float
    {
        if (empty($value)) return 0;
        if (is_numeric($value)) return (float)$value;

        $str = (string)$value;
        $str = preg_replace('/[Rp\$\s]/i', '', $str);

        if (str_contains($str, ',') && str_contains($str, '.')) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } elseif (str_contains($str, ',')) {
            $str = str_replace(',', '', $str);
        }

        return (float)$str;
    }

    private function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float)$value;
        return (float)str_replace(',', '.', (string)$value);
    }

    private function detectCurrency($value): string
    {
        if (is_string($value)) {
            if (str_contains($value, '$') || str_contains(strtoupper($value), 'USD')) {
                return 'USD';
            }
        }
        return 'IDR';
    }
}

