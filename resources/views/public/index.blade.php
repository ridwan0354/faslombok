@extends('layouts.app')

@section('title', 'Festival Anak Sholeh Lombok 2026 - Portal Resmi')

@section('body')
<div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <header class="bg-emerald-900 text-white shadow-md sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-400 to-emerald-400 flex items-center justify-center text-emerald-950 font-black text-lg shadow-sm">
                    FAS
                </div>
                <div>
                    <span class="text-base font-bold tracking-tight block leading-tight">FAS Lombok 2026</span>
                    <span class="text-xs text-emerald-300">Festival Anak Sholeh</span>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-4">
                <a href="{{ route('pengumuman') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-800 text-emerald-100 hover:bg-emerald-700 transition-colors">
                    Pengumuman Hasil
                </a>
                <a href="{{ route('juara-umum') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-400 text-emerald-950 hover:bg-amber-300 font-bold transition-colors">
                    Juara Umum
                </a>
                <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-emerald-900 hover:bg-slate-100 transition-colors shadow-xs">
                    Login Juri/Panitia
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Banner -->
    <section class="bg-gradient-to-b from-emerald-900 to-emerald-800 text-white py-12 px-4 sm:px-6 lg:px-8 text-center relative overflow-hidden">
        <div class="max-w-4xl mx-auto relative z-10">
            <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-400 text-emerald-950 mb-3 shadow-sm">
                Event Resmi • Lombok 2026
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight mb-4">
                Festival Anak Sholeh Lombok 2026
            </h1>
            <p class="text-base sm:text-lg text-emerald-100 max-w-2xl mx-auto font-light leading-relaxed">
                Mencetak Generasi Al-Qur'ani yang Berkarakter Luhur, Mandiri, dan Berakhlakul Karimah.
            </p>

            <!-- Quick Info Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mt-8 max-w-3xl mx-auto">
                <div class="bg-emerald-950/40 backdrop-blur-md rounded-2xl p-4 border border-emerald-700/40">
                    <span class="block text-2xl sm:text-3xl font-extrabold text-amber-400">{{ $event->competitions_count }}</span>
                    <span class="text-xs text-emerald-200 font-medium">Cabang Lomba</span>
                </div>
                <div class="bg-emerald-950/40 backdrop-blur-md rounded-2xl p-4 border border-emerald-700/40">
                    <span class="block text-2xl sm:text-3xl font-extrabold text-amber-400">{{ $event->participants_count }}</span>
                    <span class="text-xs text-emerald-200 font-medium">Peserta Terdaftar</span>
                </div>
                <div class="bg-emerald-950/40 backdrop-blur-md rounded-2xl p-4 border border-emerald-700/40">
                    <span class="block text-2xl sm:text-3xl font-extrabold text-amber-400">{{ $event->groups_count }}</span>
                    <span class="text-xs text-emerald-200 font-medium">Kelompok / TPQ</span>
                </div>
                <div class="bg-emerald-950/40 backdrop-blur-md rounded-2xl p-4 border border-emerald-700/40">
                    <span class="block text-2xl sm:text-3xl font-extrabold text-amber-400">{{ $publishedCount }}</span>
                    <span class="text-xs text-emerald-200 font-medium">Hasil Diumumkan</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content: Competitions Grid -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-1 w-full">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Daftar 12 Cabang Perlombaan</h2>
                <p class="text-xs sm:text-sm text-slate-500">Ketuk salah satu cabang lomba untuk melihat informasi atau hasil resmi.</p>
            </div>
            <a href="{{ route('pengumuman') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center space-x-1">
                <span>Lihat Pengumuman</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($competitions as $comp)
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $comp->format === 'team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $comp->format === 'team' ? 'Beregu' : 'Individu' }}
                        </span>

                        @if($comp->status === 'published')
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 flex items-center space-x-1">
                                <span>★ Resmi Diumumkan</span>
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600">
                                Sedang Berlangsung
                            </span>
                        @endif
                    </div>

                    <h3 class="text-base font-bold text-slate-900 mb-1.5">{{ $comp->name }}</h3>

                    <div class="space-y-1 text-xs text-slate-600 mb-4">
                        <div class="flex items-center">
                            <span class="font-medium text-slate-400 w-24">Tingkat Kelas:</span>
                            <span class="font-semibold text-slate-700">{{ $comp->eligibility_rule ?? 'Semua Tingkat' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-medium text-slate-400 w-24">Lokasi:</span>
                            <span class="text-slate-700">{{ $comp->location ?? 'Panggung Utama' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    @if($comp->status === 'published')
                        <a href="{{ route('pengumuman.detail', $comp->id) }}" 
                           class="w-full inline-flex justify-center items-center py-2 px-3 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                            Lihat Pemenang & Nilai ↗
                        </a>
                    @else
                        <span class="w-full inline-flex justify-center items-center py-2 px-3 rounded-xl text-xs font-medium bg-slate-100 text-slate-500">
                            Hasil belum diumumkan
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-emerald-950 text-emerald-400/80 py-8 text-center text-xs border-t border-emerald-900">
        <div class="max-w-4xl mx-auto px-4">
            <p class="font-bold text-white mb-1">Festival Anak Sholeh Lombok 2026</p>
            <p>Sistem Informasi Penilaian, Antrean, QR Code Peserta & Publikasi Hasil Juara</p>
            <p class="mt-3 text-emerald-500 text-[11px]">Zona Waktu Acara: WITA (Asia/Makassar)</p>
        </div>
    </footer>
</div>
@endsection
