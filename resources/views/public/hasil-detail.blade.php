@extends('layouts.app')

@section('title', "Hasil Resmi: {$competition->name} - FAS Lombok 2026")

@section('body')
<div class="min-h-screen bg-gradient-to-b from-purple-50/50 via-white to-amber-50/30 flex flex-col">
    <!-- Navbar -->
    <header class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-white shadow-lg sticky top-0 z-30 border-b border-amber-400/30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('pengumuman') }}" class="flex items-center space-x-2 text-xs font-semibold text-purple-200 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Kembali ke Pengumuman</span>
            </a>
            <div class="flex items-center space-x-2">
                <a href="{{ route('juara-umum') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gradient-to-r from-amber-400 to-amber-500 text-purple-950 hover:from-amber-300 hover:to-amber-400 shadow-xs">
                    Juara Umum
                </a>
            </div>
        </div>
    </header>

    <div class="bg-gradient-to-b from-purple-950 via-purple-900 to-purple-800 text-white py-10 px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <span class="inline-block px-3 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-pink-600 text-white mb-2 shadow-xs">
                Format: {{ $competition->format === 'team' ? 'Beregu' : 'Individu' }}
            </span>
            <h1 class="text-2xl sm:text-4xl font-black tracking-tight">{{ $competition->name }}</h1>
            <p class="text-xs sm:text-sm text-purple-200 mt-2">
                Dipublikasikan pada: {{ \App\Models\Event::formatWita($publication->published_at) }} WITA (Versi {{ $publication->version }})
            </p>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        <div class="bg-white rounded-3xl border border-purple-100 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-purple-100 bg-purple-50/50 flex items-center justify-between">
                <h3 class="text-base font-extrabold text-purple-950">Rekapitulasi Nilai & Peringkat Akhir</h3>
                <span class="text-xs text-purple-700 font-bold bg-purple-100 px-2.5 py-1 rounded-full">Total Peserta/Tim: {{ count($leaderboard) }}</span>
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
                        @forelse($leaderboard as $row)
                        <tr class="hover:bg-purple-50/30 transition-colors {{ $row['rank'] <= 3 ? 'bg-amber-50/20' : '' }}">
                            <td class="py-3.5 px-4 text-center font-bold">
                                @if($row['rank'] == 1)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-400 text-purple-950 font-black text-xs shadow-xs">1</span>
                                @elseif($row['rank'] == 2)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-300 text-slate-900 font-black text-xs">2</span>
                                @elseif($row['rank'] == 3)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-200 text-amber-950 font-black text-xs">3</span>
                                @else
                                    <span class="text-slate-500 font-mono">#{{ $row['rank'] }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if(!empty($row['award_label']))
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold {{ $row['rank'] == 1 ? 'bg-amber-400 text-purple-950' : 'bg-purple-100 text-purple-900' }}">
                                        {{ $row['award_label'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $row['name'] }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                {{ $row['group'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                    Final Resmi
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-base text-purple-950">
                                {{ number_format($row['final_score'], 1) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">Belum ada rekapitulasi data nilai.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-purple-300 py-6 text-center text-xs border-t-2 border-amber-400/40">
        <p class="font-bold text-white">Festival Anak Sholeh & Bazar Remaja Lombok 2026</p>
    </footer>
</div>
@endsection
