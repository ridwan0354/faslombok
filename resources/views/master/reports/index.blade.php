@extends('layouts.master')

@section('title', 'Laporan & Ekspor - FAS Lombok 2026')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Laporan Rekapitulasi & Ekspor Data</h1>
        <p class="text-xs text-slate-500 mt-1">Unduh berkas rekap nilai format CSV/Excel dan laporan resmi juara per cabang lomba.</p>
    </div>

    <!-- Group Champion Standings -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Rekapitulasi Klasemen Juara Umum Kelompok / TPQ</h3>
                <p class="text-xs text-slate-500">Poin dihitung dari seluruh cabang lomba yang telah dipublikasikan.</p>
            </div>
            <a href="{{ route('juara-umum') }}" target="_blank" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">
                Lihat Tampilan Publik ↗
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4 text-center w-16">Peringkat</th>
                        <th class="py-3 px-4">Kelompok / TPQ</th>
                        <th class="py-3 px-4 text-center">Emas (J1)</th>
                        <th class="py-3 px-4 text-center">Perak (J2)</th>
                        <th class="py-3 px-4 text-center">Perunggu (J3)</th>
                        <th class="py-3 px-4 text-center">Harapan</th>
                        <th class="py-3 px-4 text-right">Total Poin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($groupStandings as $row)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-center font-bold">
                            #{{ $row['rank'] }}
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-900">
                            {{ $row['group']->name }}
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-amber-600">
                            {{ $row['gold_count'] }}
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-slate-500">
                            {{ $row['silver_count'] }}
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-amber-700">
                            {{ $row['bronze_count'] }}
                        </td>
                        <td class="py-3 px-4 text-center text-slate-500">
                            {{ $row['other_awards_count'] }}
                        </td>
                        <td class="py-3 px-4 text-right font-black text-emerald-800 font-mono text-base">
                            {{ $row['total_points'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-400">
                            Belum ada perolehan poin juara yang dipublikasikan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Competitions Export List -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900">Unduh Rekap Nilai per Cabang Lomba</h3>
            <p class="text-xs text-slate-500">Ekspor berkas CSV berisi peringkat, nama peserta, nilai akhir, dan poin kelompok.</p>
        </div>

        <div class="divide-y divide-slate-100">
            @foreach($competitions as $c)
            <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                <div>
                    <h4 class="text-sm font-bold text-slate-900">{{ $c->name }}</h4>
                    <p class="text-xs text-slate-500">Format: {{ ucfirst($c->format) }} • Status: {{ ucfirst($c->status) }}</p>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('master.reports.export.csv', $c->id) }}" 
                       class="px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold text-xs transition-colors flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Unduh CSV</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
