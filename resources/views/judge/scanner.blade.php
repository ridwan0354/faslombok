@extends('layouts.judge')

@section('title', 'Pindai QR Peserta - FAS Lombok 2026')

@section('content')
<div class="space-y-4 pt-2" x-data="{
    scanner: null,
    scanning: false,
    manualCode: '',
    isLoading: false,
    resolvedData: null,
    errorMessage: '',
    cameraError: '',
    
    init() {
        this.startScanner();
    },
    
    async startScanner() {
        this.cameraError = '';
        try {
            this.scanner = new Html5Qrcode('qr-reader');
            const config = { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };
            
            await this.scanner.start(
                { facingMode: 'environment' }, 
                config, 
                (decodedText) => {
                    this.onScanSuccess(decodedText);
                },
                (error) => {
                    // ignore frame read errors
                }
            );
            this.scanning = true;
        } catch (err) {
            console.warn('Camera access failed or unavailable:', err);
            this.cameraError = 'Kamera tidak dapat diakses atau izin ditolak. Anda dapat menggunakan input kode manual di bawah.';
            this.scanning = false;
        }
    },

    async stopScanner() {
        if (this.scanner && this.scanning) {
            try {
                await this.scanner.stop();
                this.scanning = false;
            } catch (e) {
                console.error(e);
            }
        }
    },

    async onScanSuccess(decodedText) {
        if (this.isLoading) return;
        this.stopScanner();
        this.resolveToken(decodedText);
    },

    async submitManual() {
        if (!this.manualCode.trim()) return;
        this.resolveToken(this.manualCode.trim());
    },

    async resolveToken(token) {
        this.isLoading = true;
        this.errorMessage = '';
        this.resolvedData = null;

        try {
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            const res = await fetch('{{ route('judge.resolve-qr') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ token: token }),
            });

            const data = await res.json();
            this.isLoading = false;

            if (data.status === 'success') {
                this.resolvedData = data;
            } else if (data.status === 'unauthorized') {
                this.errorMessage = data.message || 'Peserta terdaftar, namun tidak ada cabang lomba yang ditugaskan kepada Anda.';
            } else {
                this.errorMessage = data.message || 'QR Code tidak valid atau tidak ditemukan.';
            }
        } catch (err) {
            this.isLoading = false;
            this.errorMessage = 'Terjadi kesalahan jaringan atau server.';
            console.error(err);
        }
    },

    resetScanner() {
        this.resolvedData = null;
        this.errorMessage = '';
        this.manualCode = '';
        this.startScanner();
    }
}">
    <!-- Top Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-base font-extrabold text-slate-900">Pindai QR Peserta / Tim</h2>
            <p class="text-xs text-slate-500">Arahkan kamera ke kartu QR peserta untuk membuka penilaian.</p>
        </div>
        <a href="{{ route('judge.dashboard') }}" class="p-2 text-slate-500 hover:text-slate-800 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </a>
    </div>

    <!-- Scanner Box -->
    <div class="bg-black rounded-3xl overflow-hidden shadow-lg relative min-h-[300px] flex items-center justify-center border-4 border-emerald-950">
        <div id="qr-reader" class="w-full h-full"></div>

        <div x-show="isLoading" class="absolute inset-0 bg-slate-900/80 backdrop-blur-xs flex flex-col items-center justify-center text-white z-20">
            <svg class="animate-spin h-8 w-8 text-emerald-400 mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-xs font-bold">Memeriksa QR & Hak Akses...</span>
        </div>

        <div x-show="cameraError" class="p-6 text-center text-white z-10">
            <svg class="w-10 h-10 text-amber-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p class="text-xs text-slate-300" x-text="cameraError"></p>
        </div>
    </div>

    <!-- Manual Code Input Fallback -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
        <label for="code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Atau Ketik Kode Peserta / Token
        </label>
        <div class="flex space-x-2">
            <input type="text" id="code" x-model="manualCode" @keydown.enter.prevent="submitManual()"
                   placeholder="Contoh: FAS-2026-0001 atau FAS-P-..."
                   class="flex-1 px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none uppercase font-mono">
            <button type="button" @click="submitManual()"
                    class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition-colors">
                Cari
            </button>
        </div>
    </div>

    <!-- Error Denial Card -->
    <div x-show="errorMessage" x-cloak 
         class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-xs">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1">
                <h4 class="text-xs font-bold text-rose-950">Akses Ditolak / Tidak Cocok</h4>
                <p class="text-xs text-rose-800 mt-0.5" x-text="errorMessage"></p>
                <button type="button" @click="resetScanner()" 
                        class="mt-3 px-3 py-1.5 bg-rose-200 hover:bg-rose-300 text-rose-900 rounded-lg text-xs font-bold transition-colors">
                    Pindai Ulang
                </button>
            </div>
        </div>
    </div>

    <!-- Successful Resolve Modal / Card -->
    <div x-show="resolvedData" x-cloak 
         class="bg-white rounded-3xl p-5 border border-emerald-300 shadow-xl space-y-4">
        <div class="flex items-start justify-between border-b border-slate-100 pb-3">
            <div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase"
                      x-text="resolvedData ? resolvedData.entity_type : ''"></span>
                <h3 class="text-base font-extrabold text-slate-900 mt-1" 
                    x-text="resolvedData && resolvedData.entity ? resolvedData.entity.name : ''"></h3>
                <p class="text-xs text-slate-500" 
                   x-text="resolvedData && resolvedData.entity ? (resolvedData.entity.code + ' • ' + resolvedData.entity.group) : ''"></p>
            </div>
            <button @click="resetScanner()" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Cabang Lomba yang Cocok & Berhak Anda Nilai:</h4>
            <div class="space-y-2">
                <template x-for="entry in (resolvedData ? resolvedData.entries : [])" :key="entry.id">
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-sm font-bold text-slate-900 block" x-text="entry.competition_name"></span>
                            <span class="text-[11px] text-slate-500" x-text="'No. Antre: #' + (entry.queue_number || '-') + ' • Status: ' + entry.status"></span>
                        </div>
                        <a :href="'/juri/nilai/' + entry.id" 
                           class="py-2 px-3.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors">
                            Buka Form Nilai →
                        </a>
                    </div>
                </template>
            </div>
        </div>

        <div class="pt-2 text-center">
            <button type="button" @click="resetScanner()" 
                    class="text-xs font-bold text-slate-500 hover:text-slate-800">
                Pindai Peserta Lain
            </button>
        </div>
    </div>
</div>
@endsection
