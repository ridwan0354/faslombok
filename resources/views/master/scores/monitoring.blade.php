@extends('layouts.master')

@section('title', 'Monitoring Nilai & Audit - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{ selectedSubmission: null }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Monitoring Nilai & Matriks Juri</h1>
            <p class="text-xs text-slate-500 mt-1">Matriks perbandingan nilai tiap juri, kalkulasi nilai akhir tertimbang, dan status kelengkapan.</p>
        </div>

        <!-- Competition Selector Dropdown -->
        <div class="flex items-center space-x-2">
            <label class="text-xs font-bold text-slate-600 uppercase">Pilih Lomba:</label>
            <form action="{{ route('master.scores') }}" method="GET" class="inline-block">
                <select name="competition_id" onchange="this.form.submit()" 
                        class="px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-bold text-slate-800 bg-white">
                    @foreach($competitions as $c)
                        <option value="{{ $c->id }}" {{ $selectedComp && $selectedComp->id === $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($selectedComp)
    <!-- Matrix Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ $selectedComp->name }}</h3>
                <p class="text-xs text-slate-500">
                    Rubrik: {{ $selectedComp->activeRubric ? 'v' . $selectedComp->activeRubric->version : '-' }} • 
                    Status: {{ $selectedComp->is_locked ? 'Terkunci' : 'Terbuka' }} • 
                    {{ $selectedComp->status === 'published' ? 'Dipublikasikan' : 'Belum Publikasi' }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('master.reports.export.csv', $selectedComp->id) }}" 
                   class="px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors">
                    Ekspor CSV Rekap Nilai
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Peserta / Tim</th>
                        <th class="py-3 px-4">Kafilah</th>
                        @foreach($judges as $j)
                            <th class="py-3 px-4 text-center">
                                <span class="font-bold text-slate-800 block">{{ $j->user->name }}</span>
                                <span class="text-[10px] text-slate-400 font-normal">{{ $j->required_for_completion ? '(Juri Wajib)' : '(Juri Tambahan)' }}</span>
                            </th>
                        @endforeach
                        <th class="py-3 px-4 text-right">Nilai Akhir Total</th>
                        <th class="py-3 px-4 text-center">Kelengkapan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($matrix as $idx => $row)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-4 text-center font-mono text-slate-400">
                            {{ $idx + 1 }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-slate-900 block">{{ $row['name'] }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $row['code'] }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $row['group'] }}
                        </td>

                        <!-- Judge Scores Columns -->
                        @foreach($judges as $j)
                        @php
                            $sc = $row['judge_scores'][$j->id] ?? null;
                        @endphp
                        <td class="py-3.5 px-4 text-center font-mono">
                            @if($sc)
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold {{ $sc['status'] === 'submitted' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ number_format($sc['score_total'], 1) }}
                                </span>
                            @else
                                <span class="text-slate-300 text-xs font-sans">Belum ada</span>
                            @endif
                        </td>
                        @endforeach

                        <!-- Final Aggregate Score -->
                        <td class="py-3.5 px-4 text-right">
                            <span class="text-base font-black text-emerald-800 font-mono">
                                {{ number_format($row['final_score'], 1) }}
                            </span>
                        </td>

                        <!-- Completion Status -->
                        <td class="py-3.5 px-4 text-center">
                            @if($row['is_complete'])
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    Lengkap
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                    Menunggu
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 5 + $judges->count() }}" class="py-8 text-center text-slate-400">
                            Belum ada peserta dalam cabang lomba ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
