<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\ImportLog;
use App\Models\BudgetRealisasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('layouts.admin')] class extends Component {
    use WithPagination, WithFileUploads;

    public $searchDate = '';
    public $searchBranch = '';

    // Upload properties
    public $csvFile;
    public $importReportDate;
    public $showUploadModal = false;

    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:10240',
        'importReportDate' => 'required|date',
    ];

    public function mount()
    {
        $this->importReportDate = date('Y-m-d');
    }

    public function openUploadModal()
    {
        $this->resetValidation();
        $this->reset('csvFile');
        $this->showUploadModal = true;
    }

    public function closeUploadModal()
    {
        $this->showUploadModal = false;
        $this->reset('csvFile');
    }

    /**
     * Konversi nilai string/angka dari CSV SAP ke integer/bigint yang aman
     */
    private function parseNumeric($val)
    {
        if (is_null($val) || $val === '') return 0;
        
        // Hapus petik ganda dan spasi
        $val = trim(str_replace('"', '', (string) $val));
        if ($val === '') return 0;
        
        // Tangani format negatif khas SAP (misal: 100.000,00- atau (100.000,00))
        $isNegative = false;
        if (str_ends_with($val, '-')) {
            $isNegative = true;
            $val = rtrim($val, '-');
        } elseif (str_starts_with($val, '(') && str_ends_with($val, ')')) {
            $isNegative = true;
            $val = trim($val, '()');
        }

        // Jika terdapat koma desimal (format Indonesia/Jerman SAP)
        if (str_contains($val, ',')) {
            $val = str_replace('.', '', $val); // Hapus pemisah ribuan titik
            $val = str_replace(',', '.', $val); // Ubah koma desimal ke titik
        } else {
            $val = str_replace(' ', '', $val);
        }
        
        $numericValue = (float) $val;
        if ($isNegative) {
            $numericValue = -$numericValue;
        }

        return (int) round($numericValue);
    }

    /**
     * Proses Impor CSV SAP
     */
    public function processUpload()
    {
        $this->validate();

        $filePath = $this->csvFile->getRealPath();
        $fileName = $this->csvFile->getClientOriginalName();

        // Deteksi delimiter (tab \t, titik koma ;, atau koma ,)
        $firstLine = file_get_contents($filePath, false, null, 0, 1000);
        $delimiter = "\t"; // Default TAB untuk ekspor SAP
        if (substr_count($firstLine, ';') > substr_count($firstLine, "\t")) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, ',') > substr_count($firstLine, "\t")) {
            $delimiter = ',';
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) return;

        // Lewati baris header CSV
        fgetcsv($handle, 0, $delimiter);

        $rowsImported = 0;
        $skippedCount = 0;
        $branches = [];
        $items = [];
        $pendingItems = [];

        DB::transaction(function () use ($handle, $delimiter, $fileName, &$rowsImported, &$skippedCount, &$branches, &$items, &$pendingItems) {
            $importLog = ImportLog::create([
                'file_name'     => $fileName,
                'report_date'   => $this->importReportDate,
                'source'        => 'upload',
                'rows_imported' => 0,
                'branches_count'=> 0,
                'items_count'   => 0,
                'skipped_count' => 0,
            ]);

            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                if (empty($row) || count($row) < 2) {
                    $skippedCount++;
                    continue;
                }

                // Bersihkan karakter petik ganda dari kolom utama
                $firstCol = trim(str_replace('"', '', $row[0] ?? ''));
                if (empty($firstCol)) {
                    $skippedCount++;
                    continue;
                }

                // Sesuai Indeks Kolom CSV SAP:
                // Index 1  = RKAP
                // Index 3  = Release Budget
                // Index 8  = Commitment
                // Index 10 = Total Consume
                // Index 11 = Available Budget
                $rkap            = $this->parseNumeric($row[1] ?? 0);
                $releaseBudget   = $this->parseNumeric($row[3] ?? 0);
                $commitment      = $this->parseNumeric($row[8] ?? 0);
                $totalConsume    = $this->parseNumeric($row[10] ?? 0);
                $availableBudget = $this->parseNumeric($row[11] ?? 0);

                // 1. Kategori Total (Grand Total / Funds Center)
                if (str_contains(strtolower($firstCol), 'funds center')) {
                    BudgetRealisasi::updateOrCreate([
                        'report_date' => $this->importReportDate,
                        'branch_code' => null,
                        'item_code'   => null,
                        'level'       => 'total',
                    ], [
                        'import_id'        => $importLog->id,
                        'branch_name'      => 'TOTAL SELURUH CABANG',
                        'item_name'        => 'Grand Total',
                        'rkap'             => $rkap,
                        'release_budget'   => $releaseBudget,
                        'commitment'       => $commitment,
                        'total_consume'    => $totalConsume,
                        'available_budget' => $availableBudget,
                    ]);
                    $rowsImported++;
                    continue;
                }

                // 2. Kategori Cabang / Unit
                if (preg_match('/^([A-Za-z0-9]+)\s+(.+)$/', $firstCol, $m) && (str_contains(strtolower($firstCol), 'cabang') || str_contains(strtolower($firstCol), 'unit') || preg_match('/^[A-Z]\d+/', $firstCol))) {
                    $branchCode = $m[1] ?? 'BRANCH';
                    $branchName = $m[2] ?? $firstCol;
                    $branches[$branchCode] = true;

                    // Pasangkan cabang ke semua item pending di atasnya
                    foreach ($pendingItems as $pItem) {
                        BudgetRealisasi::updateOrCreate([
                            'report_date' => $this->importReportDate,
                            'branch_code' => $branchCode,
                            'item_code'   => $pItem['item_code'],
                            'level'       => 'item',
                        ], array_merge($pItem, [
                            'import_id'   => $importLog->id,
                            'branch_name' => $branchName,
                        ]));
                        $items[$pItem['item_code']] = true;
                        $rowsImported++;
                    }
                    $pendingItems = [];

                    // Simpan ringkasan tingkat cabang
                    BudgetRealisasi::updateOrCreate([
                        'report_date' => $this->importReportDate,
                        'branch_code' => $branchCode,
                        'item_code'   => null,
                        'level'       => 'cabang',
                    ], [
                        'import_id'        => $importLog->id,
                        'branch_name'      => $branchName,
                        'item_name'        => null,
                        'rkap'             => $rkap,
                        'release_budget'   => $releaseBudget,
                        'commitment'       => $commitment,
                        'total_consume'    => $totalConsume,
                        'available_budget' => $availableBudget,
                    ]);
                    $rowsImported++;
                    continue;
                }

                // 3. Kategori Item Beban (misal: 5102021000 H-Beban Pemeliharaan...)
                if (preg_match('/^([0-9]+)\s+(.+)$/', $firstCol, $m)) {
                    $itemCode = $m[1];
                    $itemName = $m[2];
                } else {
                    $itemCode = 'ITEM';
                    $itemName = $firstCol;
                }

                $pendingItems[] = [
                    'item_code'        => $itemCode,
                    'item_name'        => $itemName,
                    'rkap'             => $rkap,
                    'release_budget'   => $releaseBudget,
                    'commitment'       => $commitment,
                    'total_consume'    => $totalConsume,
                    'available_budget' => $availableBudget,
                ];
            }

            fclose($handle);

            $importLog->update([
                'rows_imported'  => $rowsImported,
                'branches_count' => count($branches),
                'items_count'    => count($items),
                'skipped_count'  => $skippedCount,
            ]);
        });

        $this->closeUploadModal();
        session()->flash('message', 'Data CSV SAP berhasil diproses dan disimpan ke database.');
    }

    public function with(): array
    {
        $logs = ImportLog::latest()->paginate(5, ['*'], 'logsPage');
        
        $dataQuery = BudgetRealisasi::query()->with('importLog');
        
        if ($this->searchDate) {
            $dataQuery->whereDate('report_date', $this->searchDate);
        }
        if ($this->searchBranch) {
            $dataQuery->where(function($q) {
                $q->where('branch_name', 'like', '%' . $this->searchBranch . '%')
                  ->orWhere('branch_code', 'like', '%' . $this->searchBranch . '%')
                  ->orWhere('item_name', 'like', '%' . $this->searchBranch . '%')
                  ->orWhere('item_code', 'like', '%' . $this->searchBranch . '%');
            });
        }
        
        $data = $dataQuery->orderBy('report_date', 'desc')
                          ->orderBy('branch_code')
                          ->orderBy('level')
                          ->paginate(15, ['*'], 'dataPage');

        return [
            'importLogs' => $logs,
            'budgetData' => $data,
        ];
    }

    public function deleteBatch($importId)
    {
        DB::transaction(function () use ($importId) {
            BudgetRealisasi::where('import_id', $importId)->delete();
            $log = ImportLog::find($importId);
            if ($log) {
                $log->delete();
            }
        });
        
        $this->resetPage('logsPage');
        $this->resetPage('dataPage');
        $this->dispatch('refresh');
        
        session()->flash('message', 'Batch import dan seluruh baris data terkait berhasil dihapus.');
    }

    public function deleteRow($id)
    {
        $data = BudgetRealisasi::find($id);
        if ($data) {
            $data->delete();
            $this->dispatch('refresh');
            session()->flash('message', 'Data baris berhasil dihapus.');
        }
    }

    public function deleteAll()
    {
        DB::transaction(function () {
            BudgetRealisasi::query()->delete();
            ImportLog::query()->delete();
        });
        
        $this->resetPage('logsPage');
        $this->resetPage('dataPage');
        $this->dispatch('refresh');
        
        session()->flash('message', 'Semua data finance dan riwayat import berhasil dihapus.');
    }
};
?>

<div class="space-y-6">
    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Master Data Finance</h1>
            <p class="text-sm text-slate-500">Kelola dan unggah laporan realisasi anggaran SAP.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <button wire:click="openUploadModal" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-xs transition-all shadow-md shadow-indigo-200 active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload CSV SAP
            </button>

            <button wire:click.prevent="deleteAll" wire:confirm="Yakin hapus SEMUA data finance?" class="flex items-center gap-2 px-5 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-2xl font-bold text-xs transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Hapus Semua Data
            </button>
        </div>
    </header>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100 text-sm font-bold animate-pulse">
            {{ session('message') }}
        </div>
    @endif

    <!-- MODAL UPLOAD CSV -->
    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-xl border border-slate-100 space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-black text-slate-800">Upload Data CSV SAP</h3>
                    <button wire:click="closeUploadModal" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="processUpload" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Tanggal Laporan (Report Date)</label>
                        <input type="date" wire:model="importReportDate" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        @error('importReportDate') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">File CSV SAP</label>
                        <input type="file" wire:model="csvFile" accept=".csv, .txt" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('csvFile') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div wire:loading wire:target="csvFile" class="text-xs text-indigo-600 font-bold">
                        Mengunggah file ke server...
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" wire:click="closeUploadModal" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs disabled:opacity-50">
                            <span wire:loading.remove wire:target="processUpload">Proses Impor</span>
                            <span wire:loading wire:target="processUpload">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Riwayat Import -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
        <div class="mb-6">
            <h2 class="text-lg font-black text-slate-800">Riwayat Import</h2>
            <p class="text-sm text-slate-500">Daftar riwayat unggahan atau sinkronisasi data SAP terbaru.</p>
        </div>

        <div class="overflow-x-auto border border-slate-50 rounded-2xl">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center w-40">Waktu</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase">Nama File</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center">Report Date</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center">Sumber</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center">Baris/Cabang/Item</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center">Dilewati</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse ($importLogs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 text-slate-600 font-medium text-center">{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            <td class="p-4 font-bold text-slate-800">{{ $log->file_name }}</td>
                            <td class="p-4 text-slate-600 font-medium text-center">{{ $log->report_date }}</td>
                            <td class="p-4 text-slate-600 font-medium capitalize text-center">
                                <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-lg bg-indigo-50 text-indigo-700">{{ $log->source }}</span>
                            </td>
                            <td class="p-4 text-slate-600 font-medium text-center">
                                <span class="font-bold text-slate-800">{{ $log->rows_imported }}</span> / 
                                {{ $log->branches_count }} / {{ $log->items_count }}
                            </td>
                            <td class="p-4 text-red-500 font-bold text-center">{{ $log->skipped_count }}</td>
                            <td class="p-4 text-center">
                                <button wire:click.prevent="deleteBatch({{ $log->id }})" wire:confirm="Yakin hapus batch import ini beserta seluruh datanya?" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 font-medium">Belum ada riwayat import.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $importLogs->links() }}
        </div>
    </div>

    <!-- Daftar Data Masuk -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
                <h2 class="text-lg font-black text-slate-800">Daftar Data Budget Realisasi</h2>
                <p class="text-sm text-slate-500">Telusuri seluruh data yang telah masuk.</p>
            </div>
            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <input type="date" wire:model.live="searchDate" class="w-full md:w-auto px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" placeholder="Filter Tanggal">
                
                <div class="relative group w-full md:w-auto">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="searchBranch" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" placeholder="Cari Cabang / Item...">
                </div>
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-50 rounded-2xl">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center">Report Date</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center">Level</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase">Cabang</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase">Item</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-right">RKAP</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-right">Consume</th>
                        <th class="p-4 text-xs font-black text-slate-500 uppercase text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse ($budgetData as $row)
                        <tr class="hover:bg-slate-50 transition-colors {{ $row->level == 'total' ? 'bg-indigo-50/50' : '' }}">
                            <td class="p-4 text-slate-600 font-medium text-center">{{ $row->report_date }}</td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-lg {{ $row->level == 'total' ? 'bg-indigo-100 text-indigo-700' : ($row->level == 'cabang' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $row->level }}
                                </span>
                            </td>
                            <td class="p-4 text-slate-600 font-medium"><span class="font-bold text-slate-800">{{ $row->branch_code }}</span> {{ $row->branch_name }}</td>
                            <td class="p-4 text-slate-600 font-medium"><span class="font-bold text-slate-800">{{ $row->item_code }}</span> {{ $row->item_name }}</td>
                            <td class="p-4 text-slate-800 font-bold text-right">{{ number_format($row->rkap, 0, ',', '.') }}</td>
                            <td class="p-4 text-slate-800 font-bold text-right">{{ number_format($row->total_consume, 0, ',', '.') }}</td>
                            <td class="p-4 text-center">
                                <button wire:click.prevent="deleteRow({{ $row->id }})" wire:confirm="Yakin hapus data baris ini?" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 font-medium">Data tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $budgetData->links() }}
        </div>
    </div>
</div>