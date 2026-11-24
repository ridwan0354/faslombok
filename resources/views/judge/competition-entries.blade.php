@extends('layouts.judge')

@section('title', "Peserta {$competition->name} - FAS Lombok 2026")

@section('content')
<div class="space-y-4 pt-2">
    <!-- Header Card -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs">
        <div class="flex items-start justify-between">
            <div>
                <a href="{{ route('judge.dashboard') }}" class="text-xs font-semibold text-emerald-700 flex items-center space-x-1 mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Kembali ke Tugas</span>
                </a>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $competition->format === 'team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }} mb-1">
                    {{ $competition->format === 'team' ? 'Beregu' : 'Individu' }}
                </span>
                <h2 class="text-lg font-extrabold text-slate-900 leading-snug">{{ $competition->name }}</h2>
                <p class="text-xs text-slate-500">Tingkat: {{ $competition->eligibility_rule ?? 'Semua' }} • Lokasi: {{ $competition->location ?? 'Panggung Utama' }}</p>
            </div>

            <a href="{{ route('judge.scanner') }}" 
               class="p-2.5 rounded-2xl bg-emerald-700 text-white hover:bg-emerald-800 shadow-sm flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </a>
        </div>

        @if($competition->is_locked)
            <div class="mt-3 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center space-x-2">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Penilaian lomba ini telah dikunci oleh Master Admin.</span>
            </div>
        @endif
    </div>

    <!-- Entries List -->
    <div>
        <div class="flex items-center justify-between mb-2 px-1">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Daftar Antrean & Peserta ({{ $entries->count() }})</h3>
        </div>

        <div class="space-y-2.5">
            @forelse($entries as $entry)
            @php
                $mySub = $entry->scoreSubmissions->firstWhere('assignment.user_id', Auth::id());
            @endphp
            <div class="bg-white rounded-2xl p-4 border {{ $mySub ? 'border-emerald-200 bg-emerald-50/10' : 'border-slate-200' }} shadow-xs flex items-center justify-between gap-3">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 font-black text-xs flex items-center justify-center shrink-0 mt-0.5">
                        #{{ $entry->queue_number ?? '-' }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h4 class="text-sm font-bold text-slate-900 leading-snug">{{ $entry->display_name }}</h4>
                            <span class="px-2 py-0.2 rounded-full text-[10px] font-semibold {{ $entry->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($entry->status === 'performing' ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-slate-100 text-slate-600') }}">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">
                            {{ $entry->entity_code }} • {{ $entry->group ? $entry->group->name : '-' }}
                        </p>
                        @if($entry->team && $entry->team->members->isNotEmpty())
                            <p class="text-[11px] text-purple-700 mt-0.5 leading-snug">
                                Anggota ({{ $entry->team->members->count() }}): {{ $entry->team->members->pluck('name')->implode(', ') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col items-end shrink-0 space-y-1.5">
                    @if($mySub)
                        <span class="text-xs font-extrabold text-emerald-700 font-mono">
                            {{ number_format($mySub->score_total, 1) }}
                        </span>
                        <a href="{{ route('judge.scoring.form', $entry->id) }}" 
                           class="py-1 px-3 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs border border-emerald-200 transition-colors">
                            {{ $competition->is_locked ? 'Lihat' : 'Ubah' }}
                        </a>
                    @else
                        @if($assignment && $assignment->can_score && !$competition->is_locked)
                            <a href="{{ route('judge.scoring.form', $entry->id) }}" 
                               class="py-1.5 px-3.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition-colors">
                                Nilai →
                            </a>
                        @else
                            <a href="{{ route('judge.scoring.form', $entry->id) }}" 
                               class="py-1.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-colors">
                                Detail
                            </a>
                        @endif
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-200">
                <p class="text-xs">Belum ada peserta yang didaftarkan pada cabang lomba ini.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
