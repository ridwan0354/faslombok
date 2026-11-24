@extends('layouts.app')

@section('body')
<div class="flex h-screen overflow-hidden bg-slate-100/90" x-data="{ sidebarOpen: false }">
    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition-opacity ease-linear duration-300" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-40 bg-purple-950/70 backdrop-blur-xs lg:hidden" 
         @click="sidebarOpen = false"></div>

    <!-- Sidebar Navigation -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-purple-950 via-purple-900 to-purple-950 text-white transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col shadow-2xl border-r border-purple-800/50">
        <!-- Logo Header -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-purple-800/60 bg-purple-950/80">
            <div class="flex items-center space-x-3">
                <img src="/images/logo.png" alt="PPG SAK JAGAT RAYA Lombok" class="w-10 h-10 object-contain drop-shadow-md">
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-white leading-tight">FAS & Bazar Remaja</h1>
                    <span class="text-[11px] text-amber-300 font-medium">Dashboard Master Admin</span>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-purple-300 hover:text-white p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <a href="{{ route('master.dashboard') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.dashboard') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg shadow-purple-950/40 border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Overview
            </a>

            <div class="pt-4 pb-1 text-[11px] font-extrabold tracking-wider text-amber-300/80 uppercase px-3">Perlombaan</div>

            <a href="{{ route('master.competitions') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.competitions*') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Lomba & Rubrik
            </a>

            <a href="{{ route('master.participants') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.participants*') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Peserta & Impor
            </a>

            <a href="{{ route('master.print.qr') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.print.qr*') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                Cetak Badge QR
            </a>

            <div class="pt-4 pb-1 text-[11px] font-extrabold tracking-wider text-amber-300/80 uppercase px-3">Operasional & Juri</div>

            <a href="{{ route('master.judges') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.judges*') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Juri & Hak Akses
            </a>

            <a href="{{ route('master.scores') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.scores*') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Monitoring Nilai & Audit
            </a>

            <div class="pt-4 pb-1 text-[11px] font-extrabold tracking-wider text-amber-300/80 uppercase px-3">Hasil & Laporan</div>

            <a href="{{ route('master.reports') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.reports*') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Laporan & Ekspor
            </a>

            <a href="{{ route('master.certificates') }}" 
               class="flex items-center px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ request()->routeIs('master.certificates*') ? 'bg-gradient-to-r from-purple-700 to-pink-700 text-white shadow-lg border border-purple-400/30' : 'text-purple-200/80 hover:bg-purple-800/50 hover:text-white' }}">
                <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Cetak Piagam / Sertifikat
            </a>

            <div class="pt-3 border-t border-purple-800/50">
                <a href="{{ route('home') }}" target="_blank"
                   class="flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-amber-300 hover:bg-amber-400/10 transition-all">
                    <svg class="w-4 h-4 mr-2.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Lihat Web Publik ↗
                </a>
            </div>
        </nav>

        <!-- Current User Info & Logout -->
        <div class="p-4 border-t border-purple-800/60 bg-purple-950/90">
            <div class="flex items-center justify-between">
                <div class="truncate mr-2">
                    <p class="text-xs sm:text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[11px] text-amber-300 font-mono">{{ Auth::user()->phone }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="p-2 text-rose-300 hover:text-white hover:bg-rose-900/50 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-purple-100 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 shadow-xs">
            <div class="flex items-center space-x-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-purple-900 hover:bg-purple-50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-900 border border-purple-200">
                        Event Aktif
                    </span>
                    <span class="text-xs sm:text-sm font-extrabold text-purple-950 hidden sm:inline">Festival Anak Sholeh & Bazar Remaja 2026</span>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="text-xs text-slate-500 hidden md:block">
                    <span class="font-medium text-slate-700">Zona Waktu:</span> WITA (Lombok)
                </div>
                <div class="h-4 w-px bg-slate-200 hidden md:block"></div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('judge.dashboard') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-pink-50 text-pink-700 hover:bg-pink-100 border border-pink-200 transition-colors">
                        Buka Mode Juri Mobile
                    </a>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-4 sm:px-6 lg:px-8 pt-4">
            @if(session('success'))
                <div class="p-3.5 mb-3 rounded-2xl bg-purple-50 border border-purple-200 text-purple-900 flex items-start space-x-3 shadow-xs">
                    <svg class="w-5 h-5 text-purple-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-xs sm:text-sm font-semibold">{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-3.5 mb-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start space-x-3 shadow-xs">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-xs sm:text-sm font-semibold">{{ session('error') }}</div>
                </div>
            @endif
        </div>

        <!-- Scrollable Main View -->
        <main class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-4">
            @yield('content')
        </main>
    </div>
</div>
@endsection
