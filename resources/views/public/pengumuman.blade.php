@extends('layouts.app')

@section('title', 'Pengumuman Hasil Juara - FAS & Bazar Remaja 2026')

@section('body')
<div class="min-h-screen bg-gradient-to-b from-purple-50/50 via-white to-amber-50/30 flex flex-col">
    <!-- Navbar -->
    <header class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-white shadow-lg sticky top-0 z-30 border-b border-amber-400/30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center space-x-3">
                <img src="/images/logo.png" alt="PPG SAK JAGAT RAYA Lombok" class="w-9 h-9 object-contain drop-shadow-sm">
                <div>
                    <span class="text-sm font-bold tracking-tight block leading-tight">FAS & Bazar Remaja</span>
                    <span class="text-[11px] text-amber-300">Pengumuman Pemenang</span>
                </div>
            </a>

            <div class="flex items-center space-x-2">
                <a href="{{ route('juara-umum') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gradient-to-r from-amber-400 to-amber-500 text-purple-950 hover:from-amber-300 hover:to-amber-400 transition-colors shadow-xs">
                    Klasemen Juara Umum
                </a>
                <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-purple-950 hover:bg-purple-50 transition-colors">
                    Login
                </a>
            </div>
        </div>
    </header>

    <div class="bg-gradient-to-b from-purple-950 via-purple-900 to-purple-800 text-white py-10 px-4 text-center relative overflow-hidden">
        <div class="max-w-4xl mx-auto relative z-10">
            <span class="inline-block px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-400 text-purple-950 mb-2.5 shadow-sm">
                Surat Keputusan Dewan Juri
            </span>
            <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                Pengumuman Juara Perlombaan
            </h1>
            <p class="text-xs sm:text-sm text-purple-200 mt-2 max-w-2xl mx-auto">
                Daftar pemenang resmi yang telah ditetapkan dan dipublikasikan oleh Panitia & Dewan Juri Festival Anak Sholeh Lombok 2026.
            </p>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        @if($publishedCompetitions->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center border border-purple-200 max-w-lg mx-auto shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-purple-950 mb-1">Belum Ada Hasil yang Diumumkan</h3>
                <p class="text-xs text-slate-500 mb-6">Penilaian lomba masih dalam tahap proses atau sedang dalam peninjauan Dewan Juri.</p>
                <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-800 to-pink-700 text-white text-xs font-bold hover:from-purple-900 hover:to-pink-800 shadow-xs">
                    Kembali ke Beranda
                </a>
            </div>
        @else
            <div class="space-y-6">
                @foreach($publishedCompetitions as $comp)
                @php
                    $pub = $comp->latestPublication;
                    $snapshot = $pub ? $pub->payload_snapshot : null;
                    $leaderboard = $snapshot['leaderboard'] ?? [];
                    $winners = collect($leaderboard)->filter(fn($r) => !empty($r['award_label']))->take(4);
                @endphp
                <div class="bg-white rounded-3xl border border-purple-100 shadow-xs overflow-hidden">
                    <div class="p-5 sm:p-6 bg-purple-50/50 border-b border-purple-100 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-pink-700">Cabang Perlombaan</span>
                            <h2 class="text-lg sm:text-xl font-black text-purple-950">{{ $comp->name }}</h2>
                            <p class="text-xs text-slate-500">Format: {{ $comp->format === 'team' ? 'Beregu' : 'Individu' }} • Syarat Kelas: {{ $comp->eligibility_rule ?? 'Semua Tingkat' }}</p>
                        </div>
                        <a href="{{ route('pengumuman.detail', $comp->id) }}" 
                           class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-purple-800 to-pink-700 text-white hover:from-purple-900 hover:to-pink-800 transition-colors shadow-xs">
                            <span>Lihat Skor Lengkap</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>

                    <!-- Winners Podiums -->
                    <div class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @forelse($winners as $win)
                        <div class="rounded-2xl p-4 border {{ $win['rank'] === 1 ? 'bg-amber-50/60 border-amber-300 ring-2 ring-amber-400/40' : ($win['rank'] === 2 ? 'bg-purple-50/50 border-purple-200' : ($win['rank'] === 3 ? 'bg-pink-50/40 border-pink-200' : 'bg-slate-50 border-slate-200')) }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase {{ $win['rank'] === 1 ? 'bg-amber-400 text-purple-950' : ($win['rank'] === 2 ? 'bg-purple-200 text-purple-950' : 'bg-pink-200 text-pink-950') }}">
                                    {{ $win['award_label'] }}
                                </span>
                                <span class="text-xs font-mono font-bold text-purple-900">
                                    {{ number_format($win['final_score'], 1) }} Poin
                                </span>
                            </div>
                            <h4 class="text-sm font-bold text-purple-950 leading-snug line-clamp-1">{{ $win['name'] }}</h4>
                            <p class="text-xs text-slate-500 mt-1 truncate">{{ $win['group'] }}</p>
                            @if(!empty($win['group_points']))
                                <div class="mt-2 text-[11px] font-bold text-pink-700 flex items-center space-x-1">
                                    <span>+{{ $win['group_points'] }} Poin Juara Umum</span>
                                </div>
                            @endif
                        </div>
                        @empty
                        <div class="col-span-full py-4 text-center text-xs text-slate-400">
                            Data pemenang belum dimuat.
                        </div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-purple-300 py-8 text-center text-xs border-t-2 border-amber-400/40">
        <div class="max-w-4xl mx-auto px-4 space-y-1">
            <p class="font-bold text-white">Festival Anak Sholeh & Bazar Remaja Lombok 2026</p>
            <p class="text-amber-300">LDII & PPG SAK JAGAT RAYA Lombok • Sistem Penilaian Transparan & Akurat</p>
        </div>
    </footer>
</div>
@endsection
