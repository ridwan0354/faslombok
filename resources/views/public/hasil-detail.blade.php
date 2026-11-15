@extends('layouts.app')

@section('title', "Hasil Resmi: {$competition->name} - FAS Lombok 2026")

@section('body')
<div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <header class="bg-emerald-900 text-white shadow-md sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('pengumuman') }}" class="flex items-center space-x-2 text-xs font-semibold text-emerald-200 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Kembali ke Pengumuman</span>
            </a>
            <div class="flex items-center space-x-2">
                <a href="{{ route('juara-umum') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-400 text-emerald-950 font-bold hover:bg-amber-300">
                    Juara Umum
                </a>
            </div>
        </div>
    </header>

    <div class="bg-emerald-800 text-white py-8 px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <span class="inline-block px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-700 text-emerald-200 mb-2">
                Format: {{ $competition->format === 'team' ? 'Beregu' : 'Individu' }}
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">{{ $competition->name }}</h1>
            <p class="text-xs sm:text-sm text-emerald-100 mt-2">
                Dipublikasikan pada: {{ \Carbon\Carbon::parse($publication->published_at)->setTimezone('Asia/Makassar')->format('d M Y, H:i') }} WITA (Versi {{ $publication->version }})
            </p>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Rekapitulasi Nilai & Peringkat Akhir</h3>
                <span class="text-xs text-slate-500 font-medium">Total Peserta/Tim: {{ count($leaderboard) }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 text-center w-16">Peringkat</th>
                            <th class="py-3 px-4">Penghargaan</th>
                            <th class="py-3 px-4">Nama Peserta / Tim</th>
                            <th class="py-3 px-4">Kelompok / TPQ</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Nilai Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @foreach($leaderboard as $row)
                        <tr class="{{ !empty($row['award_label']) ? ($row['rank'] === 1 ? 'bg-amber-50/40' : 'bg-emerald-50/20') : '' }} hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 text-center">
                                @if(!empty($row['rank']))
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs {{ $row['rank'] === 1 ? 'bg-amber-400 text-amber-950 font-black' : ($row['rank'] === 2 ? 'bg-slate-300 text-slate-900 font-bold' : ($row['rank'] === 3 ? 'bg-amber-200 text-amber-900' : 'bg-slate-100 text-slate-700')) }}">
                                        {{ $row['rank'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if(!empty($row['award_label']))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $row['rank'] === 1 ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $row['award_label'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">Peserta</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $row['name'] }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $row['code'] }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $row['group'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $row['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($row['status']) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="font-extrabold text-sm sm:text-base text-emerald-800 font-mono">
                                    {{ number_format($row['final_score'], 1) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-emerald-950 text-emerald-400/80 py-6 text-center text-xs border-t border-emerald-900">
        <div class="max-w-4xl mx-auto px-4">
            <p class="font-bold text-white mb-1">Festival Anak Sholeh Lombok 2026</p>
            <p>Data hasil resmi terarsip dan terlindungi secara digital.</p>
        </div>
    </footer>
</div>
@endsection
