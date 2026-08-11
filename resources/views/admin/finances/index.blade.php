<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\ImportLog;
use App\Models\BudgetRealisasi;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.admin')] class extends Component {
    use WithPagination;

    public $searchDate = '';
    public $searchBranch = '';

    public function with(): array
    {
        $logs = ImportLog::latest()->paginate(5, ['*'], 'logsPage');
        
        $dataQuery = BudgetRealisasi::query()->with('importLog');
        
        if ($this->searchDate) {
            $dataQuery->where('report_date', $this->searchDate);
        }
        if ($this->searchBranch) {
            $dataQuery->where(function($q) {
                $q->where('branch_name', 'like', '%' . $this->searchBranch . '%')
                  ->orWhere('branch_code', 'like', '%' . $this->searchBranch . '%');
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
            $log = ImportLog::find($importId);
            if ($log) {
                $log->delete();
            }
        });
        
        $this->resetPage('logsPage');
        $this->resetPage('dataPage');
        $this->dispatch('refresh');
        
        session()->flash('message', 'Batch import dan semua baris data terkait berhasil dihapus.');
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
        
        session()->flash('message', 'Semua data finance berhasil dihapus');
    }
};
?>

<div class="space-y-6">
    <header class="flex justify-between items-center">
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Master Data Finance</h1>
        <button wire:click.prevent="deleteAll" wire:confirm="Yakin hapus SEMUA data finance?" class="flex items-center gap-2 px-5 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-2xl font-bold text-xs transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus Semua Data
        </button>
    </header>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100 text-sm font-bold animate-pulse">
            {{ session('message') }}
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
                                <button wire:click.prevent="deleteBatch({{ $log->id }})" wire:confirm="Yakin hapus data ini?" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-xl transition-colors">
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
                    <input type="text" wire:model.live.debounce.300ms="searchBranch" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500" placeholder="Cari Cabang...">
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
                                <button wire:click.prevent="deleteRow({{ $row->id }})" wire:confirm="Yakin hapus data ini?" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-xl transition-colors">
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
