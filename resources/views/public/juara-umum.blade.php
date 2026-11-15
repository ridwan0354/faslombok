@extends('layouts.app')

@section('title', 'Klasemen Juara Umum Kelompok / TPQ - FAS Lombok 2026')

@section('body')
<div class="min-h-screen bg-gradient-to-b from-purple-50/50 via-white to-amber-50/30 flex flex-col">
    <!-- Navbar -->
    <header class="bg-gradient-to-r from-purple-950 via-purple-900 to-pink-950 text-white shadow-lg sticky top-0 z-30 border-b border-amber-400/30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-amber-400 to-pink-500 flex items-center justify-center text-purple-950 font-black text-sm shadow-sm">
                    FAS
                </div>
                <div>
                    <span class="text-sm font-bold tracking-tight block leading-tight">FAS & Bazar Remaja</span>
                    <span class="text-[11px] text-amber-300">Klasemen Kelompok / TPQ</span>
                </div>
            </a>

            <div class="flex items-center space-x-2">
                <a href="{{ route('pengumuman') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-800 text-purple-100 hover:bg-purple-700">
                    Pengumuman Lomba
                </a>
            </div>
        </div>
    </header>

    <div class="bg-gradient-to-b from-purple-950 via-purple-900 to-purple-800 text-white py-10 px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <span class="inline-block px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-400 text-purple-950 mb-2 shadow-sm">
                Piala Bergilir • Juara Umum
            </span>
            <h1 class="text-2xl sm:text-4xl font-black tracking-tight">Klasemen Juara Umum Kelompok / TPQ</h1>
            <p class="text-xs sm:text-sm text-purple-200 mt-2">Akumulasi perolehan poin medali dari seluruh cabang perlombaan resmi.</p>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        <div class="bg-white rounded-3xl border border-purple-100 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-purple-100 bg-purple-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-purple-950">Peringkat Kelompok / TPQ</h3>
                    <p class="text-xs text-slate-500">Sistem Poin: Juara 1 (10 pt), Juara 2 (7 pt), Juara 3 (5 pt), Harapan (3/2/1 pt)</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                    Perhitungan Otomatis
                </span>
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
                        @forelse($standings as $row)
                        <tr class="hover:bg-purple-50/30 transition-colors {{ $row['rank'] == 1 ? 'bg-amber-50/40 font-bold' : '' }}">
                            <td class="py-3.5 px-4 text-center">
                                @if($row['rank'] == 1)
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-amber-400 text-purple-950 font-black text-sm shadow-md">
                                        🏆 1
                                    </span>
                                @elseif($row['rank'] == 2)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-300 text-slate-900 font-bold text-xs">
                                        2
                                    </span>
                                @elseif($row['rank'] == 3)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-200 text-amber-950 font-bold text-xs">
                                        3
                                    </span>
                                @else
                                    <span class="text-slate-500 font-mono">#{{ $row['rank'] }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-extrabold text-purple-950">
                                {{ $row['group_name'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-amber-600">
                                {{ $row['gold_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-600">
                                {{ $row['silver_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-amber-700">
                                {{ $row['bronze_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-500">
                                {{ $row['harapan_count'] }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-base text-purple-950">
                                {{ $row['total_points'] }} Pts
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data akumulasi klasemen kelompok.</td>
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
