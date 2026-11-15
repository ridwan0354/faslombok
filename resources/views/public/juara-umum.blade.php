@extends('layouts.app')

@section('title', 'Klasemen Juara Umum Kelompok - FAS Lombok 2026')

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
                    <span class="text-[11px] text-emerald-300">Klasemen Kafilah</span>
                </div>
            </a>

            <div class="flex items-center space-x-2">
                <a href="{{ route('pengumuman') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-800 text-white hover:bg-emerald-700">
                    Pengumuman Lomba
                </a>
            </div>
        </div>
    </header>

    <div class="bg-emerald-800 text-white py-8 px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <span class="inline-block px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-400 text-emerald-950 mb-2">
                Piala Bergilir • Juara Umum
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">Klasemen Juara Umum Kafilah / TPQ</h1>
            <p class="text-xs sm:text-sm text-emerald-100 mt-2">Akumulasi perolehan poin medali dari seluruh cabang perlombaan resmi.</p>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Peringkat Kafilah / Kelompok</h3>
                    <p class="text-xs text-slate-500">Sistem Poin: Juara 1 (10 pt), Juara 2 (7 pt), Juara 3 (5 pt), Harapan (3/2/1 pt)</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900">
                    Perhitungan Otomatis
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 text-center w-16">Peringkat</th>
                            <th class="py-3 px-4">Kafilah / TPQ</th>
                            <th class="py-3 px-4 text-center">Emas (J1)</th>
                            <th class="py-3 px-4 text-center">Perak (J2)</th>
                            <th class="py-3 px-4 text-center">Perunggu (J3)</th>
                            <th class="py-3 px-4 text-center">Harapan</th>
                            <th class="py-3 px-4 text-right">Total Poin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($standings as $row)
                        <tr class="{{ $row['rank'] === 1 ? 'bg-amber-50/50 font-bold' : ($row['rank'] === 2 ? 'bg-slate-50/50' : '') }} hover:bg-slate-50 transition-colors">
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-bold text-xs {{ $row['rank'] === 1 ? 'bg-amber-400 text-amber-950 font-black' : ($row['rank'] === 2 ? 'bg-slate-300 text-slate-900 font-bold' : ($row['rank'] === 3 ? 'bg-amber-200 text-amber-900' : 'bg-slate-100 text-slate-700')) }}">
                                    {{ $row['rank'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 text-sm sm:text-base">{{ $row['group']->name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $row['group']->code }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-amber-600">
                                {{ $row['gold_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-500">
                                {{ $row['silver_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-amber-700">
                                {{ $row['bronze_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center text-slate-500">
                                {{ $row['other_awards_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="text-base sm:text-lg font-black text-emerald-800 font-mono">
                                    {{ $row['total_points'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Belum ada data juara kelompok yang dipublikasikan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-emerald-950 text-emerald-400/80 py-6 text-center text-xs border-t border-emerald-900">
        <div class="max-w-4xl mx-auto px-4">
            <p class="font-bold text-white mb-1">Festival Anak Sholeh Lombok 2026</p>
        </div>
    </footer>
</div>
@endsection
