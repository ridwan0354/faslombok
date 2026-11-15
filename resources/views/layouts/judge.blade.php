@extends('layouts.app')

@section('body')
<div class="flex flex-col min-h-screen bg-slate-100/80 pb-20" x-data="{
    isOnline: navigator.onLine,
    draftsCount: window.FasOffline ? window.FasOffline.count() : 0,
    isSyncing: false,
    syncMessage: '',
    init() {
        window.addEventListener('online', () => { this.isOnline = true; this.triggerSync(); });
        window.addEventListener('offline', () => { this.isOnline = false; });
        window.addEventListener('fas:drafts-updated', (e) => { this.draftsCount = e.detail.length; });
    },
    async triggerSync() {
        if (!this.isOnline || this.draftsCount === 0 || this.isSyncing) return;
        this.isSyncing = true;
        this.syncMessage = 'Sedang sinkronisasi...';
        const csrf = document.querySelector('meta[name=csrf-token]').content;
        const res = await window.FasOffline.syncAll(csrf);
        this.isSyncing = false;
        if (res.success) {
            this.syncMessage = 'Sinkronisasi berhasil!';
            setTimeout(() => { this.syncMessage = ''; }, 3000);
        } else {
            this.syncMessage = 'Gagal sinkron: ' + (res.message || 'Periksa koneksi');
            setTimeout(() => { this.syncMessage = ''; }, 4000);
        }
    }
}">
    <!-- Judge Top Navigation Bar -->
    <header class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-white sticky top-0 z-30 shadow-md border-b border-amber-400/30">
        <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-400 to-pink-500 flex items-center justify-center text-purple-950 font-black text-xs shadow-sm">
                    FAS
                </div>
                <div>
                    <h1 class="text-xs sm:text-sm font-bold leading-tight">Portal Juri • FAS & Bazar Remaja</h1>
                    <p class="text-[11px] text-amber-300 truncate max-w-[180px]">{{ Auth::user()->name }}</p>
                </div>
            </div>

            <!-- Online / Offline & Sync Badge -->
            <div class="flex items-center space-x-2">
                <button @click="triggerSync()" 
                        :class="isOnline ? 'bg-purple-800 text-purple-100 hover:bg-purple-700' : 'bg-amber-600 text-white'"
                        class="px-2.5 py-1 rounded-full text-xs font-semibold flex items-center space-x-1.5 transition-all shadow-xs border border-purple-700/50">
                    <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-emerald-400 animate-pulse' : 'bg-white'"></span>
                    <span x-text="isOnline ? (draftsCount > 0 ? draftsCount + ' Draft Sync' : 'Online') : 'Offline (' + draftsCount + ')'"></span>
                </button>

                @if(Auth::user()->isMaster())
                <a href="{{ route('master.dashboard') }}" class="text-xs bg-purple-800/80 hover:bg-purple-700 px-2.5 py-1 rounded-lg text-amber-300 font-bold border border-purple-700">
                    Master
                </a>
                @endif

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="p-1.5 text-purple-300 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Sync Notification Banner -->
        <div x-show="syncMessage" x-text="syncMessage" class="bg-amber-400 text-purple-950 text-xs font-bold text-center py-1 transition-all"></div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-2xl mx-auto w-full px-4 pt-3">
        @if(session('success'))
            <div class="p-3.5 mb-2 rounded-2xl bg-purple-100 border border-purple-300 text-purple-950 text-xs sm:text-sm font-medium flex items-center space-x-2">
                <svg class="w-5 h-5 text-purple-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-3.5 mb-2 rounded-2xl bg-rose-100 border border-rose-300 text-rose-900 text-xs sm:text-sm font-medium flex items-center space-x-2">
                <svg class="w-5 h-5 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <!-- Main View Content -->
    <main class="max-w-2xl mx-auto w-full px-4 py-2 flex-1">
        @yield('content')
    </main>

    <!-- Bottom Mobile Navigation Bar -->
    <nav class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-purple-100 z-40 shadow-lg">
        <div class="max-w-md mx-auto grid grid-cols-3 h-16 items-center px-4">
            <a href="{{ route('judge.dashboard') }}" 
               class="flex flex-col items-center justify-center py-1 {{ request()->routeIs('judge.dashboard') ? 'text-purple-950 font-black' : 'text-slate-400 hover:text-slate-800' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="text-[11px]">Tugas Lomba</span>
            </a>

            <!-- Central Floating Scan Button -->
            <div class="flex justify-center -mt-6">
                <a href="{{ route('judge.scanner') }}" 
                   class="w-14 h-14 rounded-full bg-gradient-to-tr from-purple-800 via-pink-600 to-amber-500 text-white flex items-center justify-center shadow-lg shadow-purple-900/40 border-4 border-slate-100 hover:scale-105 active:scale-95 transition-transform">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </a>
            </div>

            <button @click="triggerSync()" 
                    class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-purple-900 relative">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span class="text-[11px]">Sinkronisasi</span>
                <span x-show="draftsCount > 0" x-text="draftsCount" class="absolute top-1 right-5 bg-amber-500 text-purple-950 rounded-full w-4 h-4 text-[9px] font-bold flex items-center justify-center"></span>
            </button>
        </div>
    </nav>
</div>
@endsection
