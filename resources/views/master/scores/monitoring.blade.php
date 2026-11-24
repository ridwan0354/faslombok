@extends('layouts.master')

@section('title', 'Monitoring Nilai & Audit - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{ 
    selectedDetail: null,
    showModal: false,
    openDetail(participantName, participantCode, groupName, scoreObj) {
        if (!scoreObj) return;
        this.selectedDetail = {
            participant_name: participantName,
            participant_code: participantCode,
            group_name: groupName,
            judge_name: scoreObj.judge_name,
            score_total: scoreObj.score_total,
            status: scoreObj.status,
            notes: scoreObj.notes,
            submitted_at: scoreObj.submitted_at,
            items: scoreObj.items || []
        };
        this.showModal = true;
    }
}">
    <!-- Header & Competition Selector -->
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
                        class="px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-bold text-slate-800 bg-white shadow-xs">
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
    <!-- Active Rubric Criteria Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2">
                <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">
                        Kriteria Penilaian: {{ $selectedComp->name }} (Rubrik v{{ $selectedComp->activeRubric ? $selectedComp->activeRubric->version : 1 }})
                    </h3>
                    <p class="text-xs text-slate-500">Bobot penilaian standar yang digunakan oleh dewan juri untuk cabang lomba ini.</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 font-mono">
                    Total Bobot: {{ $selectedComp->activeRubric ? $selectedComp->activeRubric->totalWeight() : 100 }}%
                </span>
                <a href="{{ route('master.competitions.detail', $selectedComp->id) }}" 
                   class="px-3 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Edit Rubrik →
                </a>
            </div>
        </div>

        <!-- Criteria Badges Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-1">
            @foreach($selectedComp->activeRubric ? $selectedComp->activeRubric->criteria : [] as $crit)
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                <span class="text-xs font-bold text-slate-900 leading-snug">{{ $crit->title }}</span>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 font-medium">Bobot Nilai</span>
                    <span class="text-xs font-mono font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        {{ (float)$crit->weight_pct }}%
                    </span>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Rating Options Scale -->
        <div class="pt-2 text-xs text-slate-500 flex flex-wrap items-center gap-2">
            <span class="font-bold text-slate-700">Skala Konversi:</span>
            @foreach($selectedComp->activeRubric ? $selectedComp->activeRubric->ratingOptions : [] as $opt)
                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 border border-slate-200 font-medium text-slate-800">
                    <strong class="text-emerald-800">{{ $opt->label }}</strong> = {{ (float)$opt->numeric_value }} Poin
                </span>
            @endforeach
        </div>
    </div>

    <!-- Matrix Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Matriks Skor Dewan Juri</h3>
                <p class="text-xs text-slate-500">
                    Klik nilai pada kolom juri untuk melihat rincian kriteria penilaian secara mendalam.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('master.reports.export.csv', $selectedComp->id) }}" 
                   class="px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors shadow-2xs">
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
                        <th class="py-3 px-4">Kelompok / TPQ</th>
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

                        <!-- Judge Scores Columns with Clickable Detail Trigger -->
                        @foreach($judges as $j)
                        @php
                            $sc = $row['judge_scores'][$j->id] ?? null;
                        @endphp
                        <td class="py-3.5 px-4 text-center font-mono">
                            @if($sc)
                                <button type="button" 
                                        @click="openDetail(@js($row['name']), @js($row['code']), @js($row['group']), @js($sc))"
                                        class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl text-xs font-extrabold {{ $sc['status'] === 'submitted' ? 'bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300' : 'bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300' }} transition-colors shadow-2xs group"
                                        title="Klik untuk melihat rincian kriteria penilaian">
                                    <span>{{ number_format($sc['score_total'], 1) }}</span>
                                    <svg class="w-3.5 h-3.5 text-emerald-700 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
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

    <!-- Modal Rincian Kriteria Penilaian Juri -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl space-y-4" @click.away="showModal = false">
            <!-- Modal Header -->
            <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                <div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                        Rincian Kriteria Penilaian Juri
                    </span>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1" 
                        x-text="selectedDetail ? selectedDetail.participant_name : ''"></h3>
                    <p class="text-xs text-slate-500" 
                       x-text="selectedDetail ? (selectedDetail.participant_code + ' • ' + selectedDetail.group_name) : ''"></p>
                </div>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Judge Meta Card -->
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex flex-wrap items-center justify-between text-xs gap-2">
                <div>
                    <span class="text-slate-400 block font-medium">Penilai:</span>
                    <span class="font-extrabold text-slate-900" x-text="selectedDetail ? selectedDetail.judge_name : ''"></span>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Waktu Submit:</span>
                    <span class="font-medium text-slate-700" x-text="selectedDetail ? (selectedDetail.submitted_at || '-') : ''"></span>
                </div>
                <div>
                    <span class="text-slate-400 block font-medium">Total Skor Juri:</span>
                    <span class="text-base font-black text-emerald-800 font-mono" x-text="selectedDetail ? selectedDetail.score_total : ''"></span>
                </div>
            </div>

            <!-- Table of Criteria Items -->
            <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Kriteria Penilaian</th>
                            <th class="py-2.5 px-3 text-center">Bobot</th>
                            <th class="py-2.5 px-3 text-center">Pilihan</th>
                            <th class="py-2.5 px-3 text-center">Nilai</th>
                            <th class="py-2.5 px-3 text-right">Poin Tertimbang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <template x-for="item in (selectedDetail ? selectedDetail.items : [])" :key="item.title">
                            <tr class="hover:bg-slate-50/80">
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-slate-900 block" x-text="item.title"></span>
                                    <span x-show="item.comment" x-text="item.comment" class="text-[10px] text-slate-400 italic block mt-0.5"></span>
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono" x-text="item.weight_pct + '%'"></td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                          :class="item.rating_label === 'Baik' ? 'bg-emerald-100 text-emerald-800' : (item.rating_label === 'Cukup' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                                          x-text="item.rating_label"></span>
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono" x-text="item.numeric_value"></td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-800 font-mono" x-text="item.weighted_score.toFixed(1)"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-bold">
                        <tr>
                            <td colspan="4" class="py-2.5 px-3 text-right text-slate-700 uppercase tracking-wider text-[11px]">
                                Total Nilai Tertimbang:
                            </td>
                            <td class="py-2.5 px-3 text-right font-black text-emerald-800 font-mono text-sm"
                                x-text="selectedDetail ? selectedDetail.score_total : ''">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes Section -->
            <div x-show="selectedDetail && selectedDetail.notes" class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-950">
                <span class="font-bold block mb-1">Catatan Tambahan Juri:</span>
                <p x-text="selectedDetail ? selectedDetail.notes : ''" class="italic text-slate-700"></p>
            </div>

            <!-- Modal Footer -->
            <div class="pt-2 flex justify-end">
                <button type="button" @click="showModal = false" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Tutup Rincian
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
