@extends('layouts.app')

@section('title', 'Pengumuman Hasil Juara - FAS Lombok 2026')

@section('body')
<div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <header class="bg-emerald-900 text-white shadow-md sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-400 to-emerald-400 flex items-center justify-center text-emerald-950 font-black text-sm shadow-sm">
                    FAS
                </div>
                <div>
                    <span class="text-sm font-bold tracking-tight block leading-tight">FAS Lombok 2026</span>
                    <span class="text-[11px] text-emerald-300">Pengumuman Pemenang</span>
                </div>
            </a>

            <div class="flex items-center space-x-2">
                <a href="{{ route('juara-umum') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-400 text-emerald-950 hover:bg-amber-300 font-bold transition-colors">
                    Klasemen Juara Umum
                </a>
                <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-emerald-900 hover:bg-slate-100 transition-colors">
                    Login
                </a>
            </div>
        </div>
    </header>

    <div class="bg-emerald-800 text-white py-8 px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <span class="inline-block px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-400 text-emerald-950 mb-2">
                Surat Keputusan Dewan Juri
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">Pengumuman Juara Perlombaan</h1>
            <p class="text-xs sm:text-sm text-emerald-100 mt-2">Daftar pemenang resmi yang telah ditetapkan dan dipublikasikan oleh Panitia & Dewan Juri.</p>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        @if($publishedCompetitions->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 max-w-lg mx-auto shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">Belum Ada Hasil yang Diumumkan</h3>
                <p class="text-xs text-slate-500 mb-6">Penilaian lomba masih dalam tahap proses atau sedang dalam peninjauan Dewan Juri.</p>
                <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800">
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
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="p-5 sm:p-6 bg-slate-50/70 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Cabang Perlombaan</span>
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">{{ $comp->name }}</h2>
                            <p class="text-xs text-slate-500">Format: {{ $comp->format === 'team' ? 'Beregu' : 'Individu' }} • Tingkat: {{ $comp->eligibility_rule ?? 'Umum' }}</p>
                        </div>
                        <a href="{{ route('pengumuman.detail', $comp->id) }}" 
                           class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-700 text-white hover:bg-emerald-800 transition-colors shadow-xs">
                            <span>Lihat Skor Lengkap</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>

                    <!-- Winners Podiums -->
                    <div class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @forelse($winners as $win)
                        <div class="rounded-2xl p-4 border {{ $win['rank'] === 1 ? 'bg-amber-50/60 border-amber-300 ring-2 ring-amber-400/30' : ($win['rank'] === 2 ? 'bg-slate-50 border-slate-300' : ($win['rank'] === 3 ? 'bg-amber-50/20 border-amber-200' : 'bg-slate-50/50 border-slate-200')) }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $win['rank'] === 1 ? 'bg-amber-400 text-amber-950' : ($win['rank'] === 2 ? 'bg-slate-200 text-slate-800' : 'bg-amber-200 text-amber-900') }}">
                                    {{ $win['award_label'] }}
                                </span>
                                <span class="text-xs font-mono font-bold text-emerald-800">
                                    {{ number_format($win['final_score'], 1) }} Poin
                                </span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 leading-snug line-clamp-1">{{ $win['name'] }}</h4>
                            <p class="text-xs text-slate-500 mt-1 truncate">{{ $win['group'] }}</p>
                            @if(!empty($win['group_points']))
                                <div class="mt-2 text-[11px] font-semibold text-emerald-700 flex items-center space-x-1">
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
    <footer class="bg-emerald-950 text-emerald-400/80 py-6 text-center text-xs border-t border-emerald-900">
        <div class="max-w-4xl mx-auto px-4">
            <p class="font-bold text-white mb-1">Festival Anak Sholeh Lombok 2026</p>
            <p>Sistem Penilaian Transparan, Terverifikasi & Akurat</p>
        </div>
    </footer>
</div>
@endsection
