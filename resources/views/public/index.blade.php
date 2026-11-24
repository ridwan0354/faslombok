@extends('layouts.app')

@section('title', 'Festival Anak Sholeh & Bazar Remaja - Lombok 2026')

@section('body')
<div class="min-h-screen bg-gradient-to-b from-purple-50/50 via-white to-amber-50/30 flex flex-col">
    <!-- Navbar -->
    <header class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-white shadow-lg sticky top-0 z-30 border-b border-amber-400/30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="/images/logo.png" alt="PPG SAK JAGAT RAYA Lombok" class="w-10 h-10 object-contain drop-shadow-md">
                <div>
                    <span class="text-sm sm:text-base font-extrabold tracking-tight block leading-tight text-white">
                        FAS & Bazar Remaja
                    </span>
                    <span class="text-[11px] text-amber-300 font-medium">Lombok 2026 • LDII & PPG</span>
                </div>
            </div>

            <div class="flex items-center space-x-2 sm:space-x-3">
                <a href="{{ route('pengumuman') }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-purple-800/80 text-purple-100 hover:bg-purple-700 hover:text-white transition-colors border border-purple-700/50">
                    Pengumuman Lomba
                </a>
                <a href="{{ route('juara-umum') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gradient-to-r from-amber-400 to-amber-500 text-purple-950 hover:from-amber-300 hover:to-amber-400 transition-colors shadow-xs">
                    Juara Umum
                </a>
                <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-purple-950 hover:bg-purple-50 transition-colors shadow-xs">
                    Login Juri/Panitia
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section with Poster Art & Theme -->
    <section class="relative overflow-hidden bg-gradient-to-b from-purple-950 via-purple-900 to-purple-800 text-white pt-8 pb-14 px-4 sm:px-6 lg:px-8">
        <!-- Decorative Islamic Arch Glow -->
        <div class="absolute inset-0 opacity-20 pointer-events-none bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-300 via-pink-500 to-transparent"></div>

        <div class="max-w-6xl mx-auto relative z-10">
            <!-- Organization Badges -->
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 mb-6">
                <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 backdrop-blur-md text-amber-300 border border-amber-400/30 shadow-xs">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>LDII (Lembaga Dakwah Islam Indonesia)</span>
                </span>
                <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 backdrop-blur-md text-amber-300 border border-amber-400/30 shadow-xs">
                    <svg class="w-3.5 h-3.5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                    <span>PPG SAK JAGAT RAYA Lombok</span>
                </span>
            </div>

            <!-- Two Column Hero on Desktop -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Left: Typography & Slogan -->
                <div class="lg:col-span-7 text-center lg:text-left space-y-4">
                    <!-- Grand Title Matching Poster -->
                    <div class="space-y-1">
                        <div class="inline-block">
                            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight drop-shadow-md text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-200 to-white">
                                Festival Anak Sholeh
                            </h1>
                        </div>
                        <div class="flex items-center justify-center lg:justify-start space-x-2 pt-1">
                            <span class="px-3 py-0.5 rounded-full bg-amber-400 text-purple-950 font-black text-xs sm:text-sm uppercase tracking-wider shadow-sm">
                                dan
                            </span>
                            <span class="px-4 py-1 rounded-2xl bg-gradient-to-r from-pink-600 via-rose-600 to-pink-700 text-white font-black text-xl sm:text-3xl shadow-lg border border-pink-400/40">
                                Bazar Remaja
                            </span>
                        </div>
                    </div>

                    <!-- Slogan Ribbon Pill -->
                    <div class="inline-flex items-center px-4 py-2 rounded-2xl bg-gradient-to-r from-amber-100 via-amber-50 to-pink-50 text-purple-950 text-xs sm:text-sm font-extrabold border-2 border-amber-300 shadow-md">
                        <span>✨ Membangun Karakter, Menumbuhkan Teladan, Mewujudkan Generasi Masa Depan 🌿</span>
                    </div>

                    <!-- Event Metadata Bar from Poster -->
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 text-xs sm:text-sm space-y-2.5 max-w-xl mx-auto lg:mx-0 shadow-lg">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-left">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <span class="text-[10px] text-purple-200 block uppercase font-bold">Tanggal</span>
                                    <span class="font-bold text-white">20 Desember 2026</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <span class="text-[10px] text-purple-200 block uppercase font-bold">Waktu</span>
                                    <span class="font-bold text-white">08.00 WITA – Selesai</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <span class="text-[10px] text-purple-200 block uppercase font-bold">Lokasi</span>
                                    <span class="font-bold text-white">Masjid Uswatun Hasanah ~ LDII Lombok</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Metrics Counter -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-2 max-w-xl mx-auto lg:mx-0">
                        <div class="bg-purple-950/60 rounded-xl p-3 border border-purple-700/50 text-center">
                            <span class="block text-2xl font-black text-amber-300 font-mono">{{ $event->competitions_count }}</span>
                            <span class="text-[11px] text-purple-200 font-medium">Cabang Lomba</span>
                        </div>
                        <div class="bg-purple-950/60 rounded-xl p-3 border border-purple-700/50 text-center">
                            <span class="block text-2xl font-black text-amber-300 font-mono">{{ $event->participants_count }}</span>
                            <span class="text-[11px] text-purple-200 font-medium">Peserta Terdaftar</span>
                        </div>
                        <div class="bg-purple-950/60 rounded-xl p-3 border border-purple-700/50 text-center">
                            <span class="block text-2xl font-black text-amber-300 font-mono">{{ $event->groups_count }}</span>
                            <span class="text-[11px] text-purple-200 font-medium">Kelompok / TPQ</span>
                        </div>
                        <div class="bg-purple-950/60 rounded-xl p-3 border border-purple-700/50 text-center">
                            <span class="block text-2xl font-black text-amber-300 font-mono">{{ $publishedCount }}</span>
                            <span class="text-[11px] text-purple-200 font-medium">Hasil Diumumkan</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Official Poster Artwork Preview -->
                <div class="lg:col-span-5 flex justify-center">
                    <div class="relative group max-w-xs sm:max-w-sm w-full">
                        <div class="absolute -inset-1 bg-gradient-to-r from-amber-400 via-pink-500 to-purple-600 rounded-3xl blur-md opacity-75 group-hover:opacity-100 transition duration-300"></div>
                        <div class="relative bg-purple-950 rounded-3xl p-2 border-2 border-amber-300 shadow-2xl overflow-hidden">
                            <img src="/images/poster.jpg" 
                                 alt="Poster Resmi Festival Anak Sholeh dan Bazar Remaja Lombok 2026" 
                                 class="w-full h-auto rounded-2xl object-cover hover:scale-[1.02] transition-transform duration-300">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2 Main Activity Pillars from Poster -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Pillar 1: Rangkaian Kegiatan FAS -->
            <div class="bg-white rounded-3xl p-6 border-2 border-purple-200 shadow-lg hover:shadow-xl transition-shadow flex items-start space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-purple-700 to-pink-600 text-amber-300 flex items-center justify-center shrink-0 shadow-md">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <div>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-purple-100 text-purple-900 mb-1">
                        Festival Santri
                    </span>
                    <h3 class="text-base sm:text-lg font-black text-purple-950">Rangkaian Kegiatan Festival Anak Sholeh</h3>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                        Berbagai kegiatan edukatif, religius, dan inspiratif untuk anak-anak dalam menumbuhkan nilai-nilai keislaman serta 29 Karakter Luhur.
                    </p>
                </div>
            </div>

            <!-- Pillar 2: Bazar Remaja -->
            <div class="bg-white rounded-3xl p-6 border-2 border-pink-200 shadow-lg hover:shadow-xl transition-shadow flex items-start space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-pink-600 to-amber-500 text-white flex items-center justify-center shrink-0 shadow-md">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <div>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-pink-100 text-pink-900 mb-1">
                        Kreativitas Generus
                    </span>
                    <h3 class="text-base sm:text-lg font-black text-purple-950">Bazar Remaja</h3>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                        Pameran, penjualan, dan berbagai produk kreatif hasil karya remaja, serta stand kuliner dan kreasi menarik lainnya.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content: Competitions Grid -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 w-full">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-purple-950">Daftar Cabang Perlombaan</h2>
                <p class="text-xs sm:text-sm text-slate-500">Pilih salah satu cabang lomba untuk melihat informasi, syarat kelas, atau pengumuman juara.</p>
            </div>
            <a href="{{ route('pengumuman') }}" class="text-xs font-bold text-pink-700 hover:text-pink-800 flex items-center space-x-1 self-start sm:self-auto">
                <span>Lihat Seluruh Pengumuman</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($competitions as $comp)
            <div class="bg-white rounded-3xl p-5 border border-purple-100 shadow-xs hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $comp->format === 'team' ? 'bg-purple-100 text-purple-900 border border-purple-200' : 'bg-pink-100 text-pink-900 border border-pink-200' }}">
                            {{ $comp->format === 'team' ? 'Beregu / Tim' : 'Individu' }}
                        </span>

                        @if($comp->status === 'published')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center space-x-1">
                                <span>★ Juara Diumumkan</span>
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                Sedang Berlangsung
                            </span>
                        @endif
                    </div>

                    <h3 class="text-base font-extrabold text-purple-950 mb-2 group-hover:text-pink-700 transition-colors">
                        {{ $comp->name }}
                    </h3>

                    <div class="space-y-1 text-xs text-slate-600 mb-4">
                        <div class="flex items-start">
                            <span class="font-medium text-slate-400 w-24 shrink-0">Syarat Kelas:</span>
                            <span class="font-bold text-purple-900">{{ $comp->eligibility_rule ?? 'Semua Tingkat Kelas' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    @if($comp->status === 'published')
                        <a href="{{ route('pengumuman.detail', $comp->id) }}" 
                           class="w-full inline-flex justify-center items-center py-2 px-3 rounded-xl text-xs font-bold bg-gradient-to-r from-purple-800 to-pink-700 text-white hover:from-purple-900 hover:to-pink-800 transition-all shadow-xs">
                            Lihat Pemenang & Nilai ↗
                        </a>
                    @else
                        <span class="w-full inline-flex justify-center items-center py-2 px-3 rounded-xl text-xs font-medium bg-slate-100 text-slate-500">
                            Penilaian sedang berjalan
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </main>

    <!-- Footer with Poster Theme -->
    <footer class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-purple-200/90 py-10 text-center text-xs border-t-2 border-amber-400/40">
        <div class="max-w-4xl mx-auto px-4 space-y-2">
            <img src="/images/logo.png" alt="PPG SAK JAGAT RAYA Lombok" class="w-16 h-16 object-contain mx-auto drop-shadow-md">
            <h4 class="font-black text-base text-white">Festival Anak Sholeh & Bazar Remaja Lombok 2026</h4>
            <p class="text-amber-300 font-medium italic">"Membangun Karakter, Menumbuhkan Teladan, Mewujudkan Generasi Masa Depan"</p>
            <p class="text-purple-300">Diselenggarakan oleh LDII (Lembaga Dakwah Islam Indonesia) & PPG SAK JAGAT RAYA Lombok</p>
            <p class="pt-2 text-[11px] text-purple-400">Masjid Uswatun Hasanah, Lombok • 20 Desember 2026 • Waktu Acara: WITA (Lombok)</p>
        </div>
    </footer>
</div>
@endsection
