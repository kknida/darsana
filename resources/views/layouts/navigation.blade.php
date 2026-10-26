@php
    // Class dasar untuk tombol
    $baseClasses = "flex items-center gap-2 px-5 py-2 text-sm transition-all duration-200 rounded-xl";
    
    // Class saat Aktif (Biru)
    $activeClasses = "bg-blue-300 text-white shadow-lg shadow-blue-200/50 ring-1 ring-blue-700/10 font-bold";
    
    // Class saat Tidak Aktif (Putih/Slate)
    $inactiveClasses = "text-slate-600 font-semibold hover:text-slate-900 hover:bg-slate-50";
@endphp

<header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex flex-col gap-2">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b-2 border-slate-100 pb-8">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-2xl shadow-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9l4.5 4.5" />
                    </svg>
                </div>
                
                <div>
                    <h1 class="text-4xl font-black tracking-tighter text-slate-900 flex items-center gap-2">
                        DARSANA
                        <span class="text-xs font-bold uppercase tracking-[0.3em] bg-red-500 text-white px-2 py-0.5 rounded-md shadow-sm">Beta</span>
                    </h1>
                    <p class="text-sm font-semibold text-slate-500 tracking-tight uppercase">
                        Dashboard <span class="text-slate-400">Real-time System</span> AirNav Analytics
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center bg-white border rounded-xl p-1 shadow-sm">
        <!-- <a href="{{ route('flight-traffic-info') }}" 
           class="{{ $baseClasses }} {{ request()->routeIs('traffic') ? $activeClasses : $inactiveClasses }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            Traffic
        </a> -->

        <a href="{{ route('flight-traffic-info') }}" 
           class="{{ $baseClasses }} {{ request()->routeIs('flight-traffic-info*') ? $activeClasses : $inactiveClasses }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform -rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            Traffic
        </a>

        <a href="{{ route('finance') }}" 
           class="{{ $baseClasses }} {{ request()->routeIs('finance') ? $activeClasses : $inactiveClasses }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Finance
        </a>

        <a href="#" 
           class="{{ $baseClasses }} {{ request()->routeIs('personnel') ? $activeClasses : $inactiveClasses }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Personnel
        </a>

        {{-- Logika Auth: Jika sudah login tampilkan Logout, jika belum tampilkan Login --}}
        @auth
            <a href="{{ route('dashboard') }}" class="{{ $baseClasses }} {{ request()->routeIs('dashboard', 'admin.*') ? $activeClasses : $inactiveClasses }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ request()->routeIs('dashboard', 'admin.*') ? '' : 'opacity-70' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04 Pel6 6 0 00-1.166 10.165C3.736 18.069 7.621 21 12 21s8.264-2.931 9.784-4.834a6 6 0 00-1.166-10.165z" />
                </svg>
                Admin
            </a>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="{{ $baseClasses }} text-red-600 hover:bg-red-50 hover:text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="{{ $baseClasses }} {{ request()->routeIs('login') ? $activeClasses : $inactiveClasses }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Login
            </a>
        @endauth
    </div>
</header>