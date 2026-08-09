<x-app-layout>
    <div class="flex flex-col gap-8 lg:flex-row">
        <aside class="w-64 shrink-0">@include('layouts.sidebar')</aside>
        <div class="flex-1 space-y-6">
        <header class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">Pengaturan Bot SAP</h1>
                <p class="text-sm text-slate-500 mt-1">Kelola konfigurasi koneksi SAP dan jadwal otomatisasi penarikan data.</p>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100 text-sm font-bold animate-pulse">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="mb-4 p-4 bg-amber-50 text-amber-700 rounded-xl border border-amber-100 text-sm font-bold">
                {{ session('warning') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-100 text-sm font-bold">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-100 text-sm font-bold">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $botLastSeen = $botStatus['botLastSeen'] ? \Carbon\Carbon::parse($botStatus['botLastSeen']) : null;
            $botColor = 'bg-red-100 text-red-700 border-red-200';
            $botText = 'Tidak Terhubung / Mati';
            $botDot = 'bg-red-500';
            if ($botLastSeen) {
                $diff = $botLastSeen->diffInMinutes(now());
                if ($diff < 5) {
                    $botColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                    $botText = 'Aktif & Terhubung';
                    $botDot = 'bg-emerald-500 animate-pulse';
                } elseif ($diff < 60) {
                    $botColor = 'bg-amber-100 text-amber-800 border-amber-200';
                    $botText = 'Terhubung ' . $diff . ' menit lalu';
                    $botDot = 'bg-amber-500';
                } else {
                    $botText = 'Terakhir terlihat ' . $botLastSeen->diffForHumans();
                }
            }
        @endphp

        <!-- Panel Status Bot -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $botColor }} border">
                    <div class="w-3 h-3 rounded-full {{ $botDot }}"></div>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Status Bot PC
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $botColor }} border">{{ $botText }}</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Komputer: <strong class="text-slate-700">{{ $botStatus['botMachineName'] ?: 'Belum pernah terhubung' }}</strong>
                        @if($botStatus['botVersion'])
                            | Versi Bot: <span class="text-slate-700">{{ $botStatus['botVersion'] }}</span>
                        @endif
                    </p>
                    <p class="text-sm text-slate-500 mt-1">
                        @if($latestImport)
                            @php
                                $diff24h = $latestImport->created_at->diffInHours(now()) >= 24;
                            @endphp
                            Data terakhir: <strong class="text-slate-700">{{ $latestImport->created_at->format('d M Y H:i') }}</strong> 
                            ({{ $latestImport->rows_imported ?? 0 }} baris)
                            @if($diff24h)
                                <span class="text-red-600 font-bold ml-2">⚠️ Bot belum mengirim data lebih dari 24 jam!</span>
                            @endif
                        @else
                            Belum ada data diimpor.
                        @endif
                    </p>
                </div>
            </div>
            
            <form action="{{ route('finance.refresh') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition-colors shadow-lg shadow-indigo-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Ambil Data Sekarang
                </button>
            </form>
        </div>

        <form action="{{ route('admin.sap-settings.update') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Section 1: Kredensial SAP -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    1. Kredensial SAP
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="sapUser" class="block text-sm font-bold text-slate-700">Username SAP</label>
                        <input type="text" id="sapUser" name="sapUser" value="{{ old('sapUser', $settings['sapUser'] ?? '') }}" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        @error('sapUser') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="sapPass" class="block text-sm font-bold text-slate-700">Password SAP</label>
                        <div class="relative">
                            <input type="password" id="sapPass" name="sapPass" placeholder="Kosongkan jika tidak ingin mengubah password"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 pr-12">
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors" title="Tampilkan/Sembunyikan">
                                <svg id="eyeIcon" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path id="eyePath" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span id="eyeText" class="hidden sm:inline">Tampilkan</span>
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Kosongkan jika password SAP tidak ingin diubah.</p>
                        @error('sapPass') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Konfigurasi Export -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"></path></svg>
                    2. Konfigurasi Export
                </h2>
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label for="exportFolder" class="block text-sm font-bold text-slate-700">Folder Export</label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" id="exportFolder" name="exportFolder" value="{{ old('exportFolder', $settings['exportFolder'] ?? '') }}" placeholder="D:\Sap_export" required
                                class="w-full pl-4 pr-3 py-3 bg-white border border-slate-300 text-slate-900 text-sm rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm">
                            <button type="button" onclick="testFolder()" class="px-5 py-3 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold rounded-2xl transition-colors shrink-0 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Test Folder
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            Masukkan path folder di komputer tempat bot berjalan, contoh: <code class="bg-slate-100 text-pink-600 px-1 py-0.5 rounded">D:\Sap_export</code>. Folder ini ada di komputer bot, bukan di server ini.
                        </p>
                        @error('exportFolder') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                        <p id="folderStatus" class="text-xs font-bold mt-2 hidden px-3 py-2 rounded-xl"></p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Jadwal Otomatisasi -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    3. Jadwal Otomatisasi
                </h2>
                <div class="space-y-3">
                    <label class="block text-sm font-bold text-slate-700">Jam Ambil Data Otomatis</label>
                    <div class="flex gap-3 items-center">
                        <select name="hour" id="hourSelect" class="w-48 px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold focus:ring-4 focus:ring-indigo-500/10" onchange="updatePreview()">
                            <option value="">Jam</option>
                            @php
                            $hourLabels = [
                                24 => '00 (12 malam)', 1 => '01 (1 pagi)', 2 => '02 (2 pagi)', 3 => '03 (3 pagi)',
                                4 => '04 (4 pagi)', 5 => '05 (5 pagi)', 6 => '06 (6 pagi)', 7 => '07 (7 pagi)',
                                8 => '08 (8 pagi)', 9 => '09 (9 pagi)', 10 => '10 (10 pagi)', 11 => '11 (11 pagi)',
                                12 => '12 (12 siang)', 13 => '13 (1 siang)', 14 => '14 (2 siang)', 15 => '15 (3 siang)',
                                16 => '16 (4 sore)', 17 => '17 (5 sore)', 18 => '18 (6 sore)', 19 => '19 (7 malam)',
                                20 => '20 (8 malam)', 21 => '21 (9 malam)', 22 => '22 (10 malam)', 23 => '23 (11 malam)',
                            ];
                            @endphp
                            @foreach([24,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23] as $i)
                                <option value="{{ $i }}" {{ old('hour', $hour ?? '') == $i ? 'selected' : '' }}>{{ $i === 24 ? '00 - 00' : str_pad($i, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($i, 2, '0', STR_PAD_LEFT) }} ({{ explode('(', $hourLabels[$i])[1] }}</option>
                            @endforeach
                        </select>
                        <span class="font-black text-slate-400 text-lg">:</span>
                        <select name="minute" id="minuteSelect" class="w-24 px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold focus:ring-4 focus:ring-indigo-500/10" onchange="updatePreview()">
                            <option value="">Menit</option>
                            @for($i = 0; $i <= 59; $i++)
                                <option value="{{ $i }}" {{ (old('minute', $minute ?? '') !== '' && old('minute', $minute ?? '') == $i) ? 'selected' : '' }}>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                        <span class="text-sm font-bold text-slate-600 bg-slate-100 px-3 py-2 rounded-xl">WIB</span>
                    </div>
                    @error('hour') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    @error('minute') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    <div id="schedulePreview" class="text-sm text-indigo-700 font-semibold mt-3 bg-indigo-50 p-4 rounded-xl border border-indigo-100 flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span id="previewText">Silakan pilih jam dan menit.</span>
                    </div>
                </div>
            </div>

            <!-- Panel Pengaturan Lanjutan -->
            <details class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 group">
                <summary class="text-xl font-bold text-slate-800 flex items-center justify-between cursor-pointer list-none outline-none">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-slate-400 group-open:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Pengaturan Lanjutan - jangan diubah tanpa arahan tim IT
                    </div>
                    <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </summary>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 border-t border-slate-100 pt-6">
                    <div class="space-y-2">
                        <label for="sapSystem" class="block text-sm font-bold text-slate-700">SAP System</label>
                        <input type="text" id="sapSystem" name="sapSystem" value="{{ old('sapSystem', $settings['sapSystem'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Nama sistem SAP, contoh PRD</p>
                        @error('sapSystem') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="sapClient" class="block text-sm font-bold text-slate-700">SAP Client</label>
                        <input type="text" id="sapClient" name="sapClient" value="{{ old('sapClient', $settings['sapClient'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Nomor client SAP, contoh 300</p>
                        @error('sapClient') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="sapLang" class="block text-sm font-bold text-slate-700">SAP Language</label>
                        <input type="text" id="sapLang" name="sapLang" value="{{ old('sapLang', $settings['sapLang'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Bahasa SAP, contoh EN</p>
                        @error('sapLang') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="reportTx" class="block text-sm font-bold text-slate-700">Report T-Code</label>
                        <input type="text" id="reportTx" name="reportTx" value="{{ old('reportTx', $settings['reportTx'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Kode transaksi laporan, contoh ZFM001</p>
                        @error('reportTx') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="fmArea" class="block text-sm font-bold text-slate-700">FM Area</label>
                        <input type="text" id="fmArea" name="fmArea" value="{{ old('fmArea', $settings['fmArea'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Funds Management area, contoh 1000</p>
                        @error('fmArea') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="fundCenterLow" class="block text-sm font-bold text-slate-700">Fund Center Low</label>
                        <input type="text" id="fundCenterLow" name="fundCenterLow" value="{{ old('fundCenterLow', $settings['fundCenterLow'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Batas bawah fund center, contoh A022020000</p>
                        @error('fundCenterLow') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="fundCenterHigh" class="block text-sm font-bold text-slate-700">Fund Center High</label>
                        <input type="text" id="fundCenterHigh" name="fundCenterHigh" value="{{ old('fundCenterHigh', $settings['fundCenterHigh'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Batas atas fund center, contoh A022020005</p>
                        @error('fundCenterHigh') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="filePrefix" class="block text-sm font-bold text-slate-700">File Prefix</label>
                        <input type="text" id="filePrefix" name="filePrefix" value="{{ old('filePrefix', $settings['filePrefix'] ?? '') }}"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm transition-all focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500">
                        <p class="text-xs text-slate-500 mt-1">Awalan nama berkas CSV hasil export. Contoh realisasi_ menghasilkan realisasi_20260809_1317.csv</p>
                        @error('filePrefix') <p class="text-xs text-red-600 font-bold mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </details>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition-colors shadow-lg shadow-indigo-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Pengaturan
                </button>
            </div>
        </form>



        <!-- Scripts -->
        <script>
        function togglePassword() {
            const input = document.getElementById('sapPass');
            const path = document.getElementById('eyePath');
            const text = document.getElementById('eyeText');
            if (input.type === 'password') {
                input.type = 'text';
                text.innerText = 'Sembunyikan';
                path.setAttribute('d', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21');
            } else {
                input.type = 'password';
                text.innerText = 'Tampilkan';
                path.setAttribute('d', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z');
            }
        }

        function updatePreview() {
            const h = document.getElementById('hourSelect').value;
            const m = document.getElementById('minuteSelect').value;
            const preview = document.getElementById('previewText');
            if (h && m !== "") {
                const hourLabels = {
                    "24": "12 malam", "1": "1 pagi", "2": "2 pagi", "3": "3 pagi", "4": "4 pagi", "5": "5 pagi",
                    "6": "6 pagi", "7": "7 pagi", "8": "8 pagi", "9": "9 pagi", "10": "10 pagi", "11": "11 pagi",
                    "12": "12 siang", "13": "1 siang", "14": "2 siang", "15": "3 siang", "16": "4 sore",
                    "17": "5 sore", "18": "6 sore", "19": "7 malam", "20": "8 malam", "21": "9 malam",
                    "22": "10 malam", "23": "11 malam"
                };
                const hourLabel = hourLabels[h];
                const displayHour = h === "24" ? "00" : h.padStart(2, '0');
                const minStr = m.toString().padStart(2, '0');
                preview.innerText = `Bot akan mengambil data otomatis setiap hari pukul ${displayHour}:${minStr} WIB (${hourLabel}).`;
                preview.parentElement.classList.remove('bg-amber-50', 'text-amber-700', 'border-amber-100');
                preview.parentElement.classList.add('bg-indigo-50', 'text-indigo-700', 'border-indigo-100');
            } else {
                preview.innerText = 'Silakan pilih jam dan menit agar jadwal aktif.';
                preview.parentElement.classList.remove('bg-indigo-50', 'text-indigo-700', 'border-indigo-100');
                preview.parentElement.classList.add('bg-amber-50', 'text-amber-700', 'border-amber-100');
            }
        }

        // Initialize preview
        document.addEventListener('DOMContentLoaded', updatePreview);

        function testFolder() {
            const folder = document.getElementById('exportFolder').value;
            const statusEl = document.getElementById('folderStatus');
            
            if (!folder) {
                statusEl.innerText = 'Folder belum diisi.';
                statusEl.className = 'text-xs font-bold mt-2 block px-3 py-2 rounded-xl bg-amber-50 text-amber-700 border border-amber-100';
                return;
            }

            // Validasi client-side regex
            // Aturan valid:
            // - Diawali drive letter Windows, contoh: C:\  D:\  E:\
            // - Tidak mengandung karakter terlarang: < > " | ? *
            // - Tidak diakhiri spasi
            const driveRegex = /^[a-zA-Z]:\\/;
            const forbiddenChars = /[<>"|?*]/;
            const endsWithSpace = folder.endsWith(' ');

            if (!driveRegex.test(folder)) {
                statusEl.innerText = 'Format path tidak valid. Harus diawali dengan Drive Letter (misal: D:\\)';
                statusEl.className = 'text-xs font-bold mt-2 block px-3 py-2 rounded-xl bg-red-50 text-red-700 border border-red-100';
                return;
            }

            if (forbiddenChars.test(folder)) {
                statusEl.innerText = 'Format path tidak valid. Mengandung karakter terlarang (< > " | ? *).';
                statusEl.className = 'text-xs font-bold mt-2 block px-3 py-2 rounded-xl bg-red-50 text-red-700 border border-red-100';
                return;
            }

            if (endsWithSpace) {
                statusEl.innerText = 'Format path tidak valid. Tidak boleh diakhiri dengan spasi.';
                statusEl.className = 'text-xs font-bold mt-2 block px-3 py-2 rounded-xl bg-red-50 text-red-700 border border-red-100';
                return;
            }

            statusEl.innerHTML = '<span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Format path valid.</span>';
            statusEl.className = 'text-xs font-bold mt-2 block px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100';
        }
        </script>
    </div>
</x-app-layout>
