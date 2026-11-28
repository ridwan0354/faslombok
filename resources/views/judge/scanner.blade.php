@extends('layouts.judge')

@section('title', 'Pindai QR Peserta - FAS Lombok 2026')

@push('styles')
<style>
    #qr-reader video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover;
        border-radius: 1.25rem;
    }
    #qr-reader img[alt="Info icon"] {
        display: none;
    }
</style>
@endpush

@section('content')
<script>
function qrScannerApp() {
    return {
        scanner: null,
        scanning: false,
        isSwitchingCamera: false,
        facingMode: 'environment',
        cameras: [],
        selectedCameraId: '',
        activeDeviceId: '',
        cameraNotice: '',
        manualCode: '',
        isLoading: false,
        resolvedData: null,
        errorMessage: '',
        cameraError: '',

        init() {
            this.startScanner(false);
        },

        qrBoxSizer(viewfinderWidth, viewfinderHeight) {
            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
            const size = Math.max(180, Math.min(260, Math.floor(minEdge * 0.72)));
            return { width: size, height: size };
        },

        scanConfig() {
            return {
                fps: 10,
                qrbox: (w, h) => this.qrBoxSizer(w, h),
                aspectRatio: 1.0,
            };
        },

        async loadCameras() {
            try {
                const list = await Html5Qrcode.getCameras();
                if (Array.isArray(list)) {
                    this.cameras = list;
                }
            } catch (e) {
                console.warn('Unable to enumerate cameras:', e);
            }
        },

        findCameraForMode(mode, excludeId = '') {
            if (!this.cameras.length) return null;
            const pattern = mode === 'user'
                ? /front|depan|user|facetime|selfie/i
                : /back|rear|belakang|environment|main|utama/i;
            const matched = this.cameras.find((cam) => pattern.test(cam.label || ''));
            if (matched) return matched;
            const fallback = this.cameras.find((cam) => cam.id !== excludeId);
            return fallback || this.cameras[0];
        },

        async startWithConstraint(constraint) {
            await this.stopScanner();
            this.scanner = new Html5Qrcode('qr-reader');
            await this.scanner.start(
                constraint,
                this.scanConfig(),
                (decodedText) => this.onScanSuccess(decodedText),
                () => {}
            );
            this.scanning = true;

            try {
                const settings = this.scanner.getRunningTrackSettings ? this.scanner.getRunningTrackSettings() : {};
                if (settings.deviceId) {
                    this.activeDeviceId = settings.deviceId;
                }
                if (settings.facingMode === 'user' || settings.facingMode === 'environment') {
                    this.facingMode = settings.facingMode;
                }
            } catch (_) {}

            await this.loadCameras();
        },

        async startScanner(preferExactFacing = false) {
            this.cameraError = '';
            this.cameraNotice = '';

            if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
                this.cameraError = 'Akses kamera membutuhkan koneksi HTTPS. Buka situs melalui https:// atau gunakan input kode manual di bawah.';
                this.scanning = false;
                return;
            }

            const prevDeviceId = this.activeDeviceId;
            const targetMode = this.facingMode;

            try {
                if (this.selectedCameraId && !preferExactFacing) {
                    await this.startWithConstraint(this.selectedCameraId);
                    return;
                }

                if (preferExactFacing) {
                    try {
                        await this.startWithConstraint({ facingMode: { exact: targetMode } });
                        return;
                    } catch (exactErr) {
                        await this.loadCameras();
                        const matched = this.findCameraForMode(targetMode, prevDeviceId);
                        if (matched && (this.cameras.length > 1 || matched.id !== prevDeviceId)) {
                            await this.startWithConstraint(matched.id);
                            return;
                        }
                        if (this.cameras.length <= 1) {
                            this.cameraNotice = 'Perangkat ini hanya memiliki 1 kamera aktif.';
                        }
                    }
                }

                await this.startWithConstraint({ facingMode: targetMode });
            } catch (err) {
                console.warn('Camera access failed or unavailable:', err);
                await this.stopScanner();
                this.cameraError = 'Kamera tidak dapat diakses atau izin ditolak. Pastikan izin kamera aktif, atau gunakan input kode manual di bawah.';
                this.scanning = false;
            }
        },

        async stopScanner() {
            if (this.scanner) {
                if (this.scanning || this.scanner.isScanning) {
                    try {
                        await this.scanner.stop();
                    } catch (e) {
                        console.warn('Stop scanner error:', e);
                    }
                }
                try {
                    this.scanner.clear();
                } catch (_) {}
                this.scanning = false;
                this.scanner = null;
            }
        },

        async toggleCamera() {
            if (this.isSwitchingCamera || this.isLoading) return;
            this.isSwitchingCamera = true;
            this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
            this.selectedCameraId = '';
            try {
                await this.startScanner(true);
            } finally {
                this.isSwitchingCamera = false;
            }
        },

        async selectCamera(cameraId) {
            if (this.isSwitchingCamera || this.isLoading || !cameraId) return;
            this.isSwitchingCamera = true;
            this.selectedCameraId = cameraId;
            const cam = this.cameras.find((c) => c.id === cameraId);
            if (cam && /front|depan|user|facetime|selfie/i.test(cam.label || '')) {
                this.facingMode = 'user';
            } else if (cam && /back|rear|belakang|environment/i.test(cam.label || '')) {
                this.facingMode = 'environment';
            }
            try {
                await this.startScanner(false);
            } finally {
                this.isSwitchingCamera = false;
            }
        },

        async onScanSuccess(decodedText) {
            if (this.isLoading) return;
            await this.stopScanner();
            await this.resolveToken(decodedText);
        },

        async submitManual() {
            if (!this.manualCode.trim()) return;
            await this.resolveToken(this.manualCode.trim());
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

        async resetScanner() {
            this.resolvedData = null;
            this.errorMessage = '';
            this.manualCode = '';
            await this.startScanner(false);
        }
    };
}
</script>

<div class="space-y-4 pt-2" x-data="qrScannerApp()">
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

    <!-- Camera Controls Bar -->
    <div class="bg-white rounded-2xl p-3 border border-slate-200 shadow-xs space-y-2.5">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full"
                      :class="scanning ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></span>
                <span class="text-xs font-bold text-slate-700"
                      x-text="isSwitchingCamera ? 'Mengganti kamera...' : (facingMode === 'environment' ? 'Kamera Belakang Aktif' : 'Kamera Depan Aktif')">
                </span>
            </div>

            <button type="button"
                    @click="toggleCamera()"
                    :disabled="isSwitchingCamera || isLoading"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-purple-900 hover:bg-purple-950 disabled:opacity-60 text-white text-xs font-bold shadow-xs transition-all cursor-pointer">
                <svg class="w-4 h-4 text-amber-300" :class="isSwitchingCamera ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="facingMode === 'environment' ? 'Ganti ke Kamera Depan' : 'Ganti ke Kamera Belakang'"></span>
            </button>
        </div>

        <template x-if="cameras.length > 2">
            <div class="flex items-center space-x-2 pt-2 border-t border-slate-100">
                <label for="camera-select" class="text-[11px] font-bold text-slate-500 shrink-0">Pilih Lensa:</label>
                <select id="camera-select"
                        :value="selectedCameraId || activeDeviceId"
                        @change="selectCamera($event.target.value)"
                        class="flex-1 px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600 font-medium">
                    <template x-for="(cam, idx) in cameras" :key="cam.id">
                        <option :value="cam.id" x-text="cam.label || ('Kamera ' + (idx + 1))"></option>
                    </template>
                </select>
            </div>
        </template>

        <p x-show="cameraNotice" x-cloak x-text="cameraNotice" class="text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-2.5 py-1.5"></p>
    </div>

    <!-- Scanner Box -->
    <div class="bg-black rounded-3xl overflow-hidden shadow-lg relative min-h-[300px] flex items-center justify-center border-4 border-emerald-950">
        <div id="qr-reader" class="w-full h-full"></div>

        <div x-show="isLoading" class="absolute inset-0 bg-slate-900/80 backdrop-blur-xs flex flex-col items-center justify-center text-white z-20">
            <svg class="animate-spin h-8 w-8 text-emerald-400 mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-xs font-bold">Memeriksa QR & Hak Akses...</span>
        </div>

        <div x-show="cameraError" x-cloak class="p-6 text-center text-white z-10 space-y-3">
            <svg class="w-10 h-10 text-amber-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p class="text-xs text-slate-300" x-text="cameraError"></p>
            <button type="button" @click="startScanner(false)"
                    class="px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-bold transition-colors cursor-pointer">
                Coba Aktifkan Kamera Lagi
            </button>
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
