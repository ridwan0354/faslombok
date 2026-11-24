@extends('layouts.judge')

@section('title', "Penilaian: {$entry->display_name} - FAS Lombok 2026")

@section('content')
@php
    $canEdit = ($assignment && $assignment->can_score && !$competition->is_locked);
    if (Auth::user()->isMaster()) {
        $canEdit = true;
    }

    $existingItems = $existingSubmission ? $existingSubmission->items->keyBy('criterion_id') : collect();
@endphp

<div class="space-y-4 pt-2 pb-24" x-data="{
    scores: {
        @foreach($rubric->criteria as $criterion)
        @php
            $item = $existingItems->get($criterion->id);
            $initialOpt = $item ? $item->rating_option_id : null;
            $initialVal = $item ? (float)$item->numeric_value : null;
        @endphp
        '{{ $criterion->id }}': {
            rating_option_id: {{ $initialOpt ? $initialOpt : 'null' }},
            numeric_value: {{ $initialVal ? $initialVal : 'null' }},
            weight_pct: {{ (float) $criterion->weight_pct }},
            comment: @js($item ? ($item->comment ?? '') : '')
        },
        @endforeach
    },
    notes: @js($existingSubmission ? ($existingSubmission->notes ?? '') : ''),
    actionType: 'submit',
    isLocked: {{ $competition->is_locked ? 'true' : 'false' }},

    selectOption(criterionId, optionId, val) {
        if (!{{ $canEdit ? 'true' : 'false' }}) return;
        this.scores[criterionId].rating_option_id = optionId;
        this.scores[criterionId].numeric_value = val;
    },

    setNumericValue(criterionId, rawVal) {
        if (!{{ $canEdit ? 'true' : 'false' }}) return;
        this.scores[criterionId].rating_option_id = null;
        if (rawVal === '' || rawVal === null) {
            this.scores[criterionId].numeric_value = null;
            return;
        }
        let num = parseFloat(rawVal);
        if (isNaN(num)) {
            this.scores[criterionId].numeric_value = null;
            return;
        }
        if (num < 50) num = 50;
        if (num > 100) num = 100;
        this.scores[criterionId].numeric_value = num;
    },

    get totalScore() {
        let total = 0.0;
        for (const cid in this.scores) {
            const sc = this.scores[cid];
            if (sc.numeric_value !== null) {
                total += (sc.numeric_value * (sc.weight_pct / 100));
            }
        }
        return Math.round(total * 10) / 10;
    },

    isFormValid() {
        for (const cid in this.scores) {
            if (this.scores[cid].numeric_value === null) {
                return false;
            }
        }
        return true;
    },

    async handleSubmit(e, type) {
        this.actionType = type;
        if (!navigator.onLine) {
            e.preventDefault();
            const clientUuid = 'fas-' + Math.random().toString(36).substring(2, 9) + '-' + Date.now();
            window.FasOffline.saveDraft({
                client_uuid: clientUuid,
                entry_id: {{ $entry->id }},
                scores: this.scores,
                notes: this.notes,
                entry_name: @js($entry->display_name),
                competition_name: @js($competition->name),
                timestamp: new Date().toISOString()
            });
            alert('Perangkat Anda sedang offline. Penilaian telah disimpan sebagai draft lokal dan akan otomatis disinkronkan saat koneksi internet pulih.');
            window.location.href = '{{ route('judge.competition.entries', $competition->id) }}';
            return;
        }
    }
}">
    <!-- Header: Participant Info -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs">
        <div class="flex items-start justify-between">
            <div>
                <a href="{{ route('judge.competition.entries', $competition->id) }}" class="text-xs font-semibold text-emerald-700 flex items-center space-x-1 mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Daftar Peserta</span>
                </a>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 mb-1">
                    No. Antre: #{{ $entry->queue_number ?? '-' }}
                </span>
                <h2 class="text-lg font-extrabold text-slate-900 leading-snug">{{ $entry->display_name }}</h2>
                <p class="text-xs text-slate-600 mt-0.5 font-medium">
                    {{ $entry->entity_code }} • {{ $entry->group ? $entry->group->name : '-' }}
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Lomba: <span class="font-bold text-slate-700">{{ $competition->name }}</span> ({{ $competition->format === 'team' ? 'Beregu' : 'Individu' }})
                </p>
            </div>

            @if($existingSubmission)
                <div class="text-right">
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $existingSubmission->status === 'submitted' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ ucfirst($existingSubmission->status) }}
                    </span>
                    <span class="block text-xl font-black text-emerald-800 font-mono mt-1">
                        {{ number_format($existingSubmission->score_total, 1) }}
                    </span>
                </div>
            @endif
        </div>

        @if($competition->is_locked)
            <div class="mt-3 p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center space-x-2">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Penilaian lomba telah dikunci. Anda hanya dapat melihat nilai.</span>
            </div>
        @endif
    </div>

    <!-- Peer Scores Section if permitted -->
    @if($peerSubmissions->isNotEmpty())
        <div class="bg-indigo-50/60 rounded-3xl p-4 border border-indigo-200 shadow-xs" x-data="{ openPeers: false }">
            <button type="button" @click="openPeers = !openPeers" class="w-full flex items-center justify-between text-left">
                <div class="flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span class="text-xs font-bold text-indigo-900">Nilai Juri Sejawat ({{ $peerSubmissions->count() }} Juri)</span>
                </div>
                <svg class="w-4 h-4 text-indigo-600 transform transition-transform" :class="openPeers ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="openPeers" x-collapse class="mt-3 space-y-2 pt-2 border-t border-indigo-200/50">
                @foreach($peerSubmissions as $ps)
                <div class="p-2.5 rounded-xl bg-white border border-indigo-100 flex items-center justify-between text-xs">
                    <div>
                        <span class="font-bold text-slate-800">{{ $ps->assignment->user->name }}</span>
                        <span class="text-[10px] text-slate-400 block">{{ ($peerTime = \App\Models\Event::formatWita($ps->submitted_at, 'H:i')) ? $peerTime . ' WITA' : '-' }}</span>
                    </div>
                    <span class="font-mono font-black text-indigo-700 text-sm">
                        {{ number_format($ps->score_total, 1) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Scoring Form Form Container -->
    <form action="{{ route('judge.scoring.submit', $entry->id) }}" method="POST" id="scoring-form">
        @csrf
        <input type="hidden" name="action_type" :value="actionType">

        <!-- Criteria List -->
        <div class="space-y-3">
            <div class="px-1 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kriteria Penilaian (Bobot 100%)</span>
                <span class="text-[11px] text-emerald-700 font-semibold">Ketuk kategori nilai</span>
            </div>

            @foreach($rubric->criteria as $criterion)
            <div class="bg-white rounded-3xl p-4 border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 leading-snug">{{ $criterion->title }}</h4>
                        <span class="inline-block mt-0.5 text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md">
                            Bobot: {{ (float) $criterion->weight_pct }}%
                        </span>
                    </div>

                    <!-- Calculated criterion weighted preview -->
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 block font-medium">Poin Bobot</span>
                        <span class="text-xs font-mono font-bold text-emerald-700" 
                              x-text="scores['{{ $criterion->id }}'].numeric_value !== null ? ((scores['{{ $criterion->id }}'].numeric_value * scores['{{ $criterion->id }}'].weight_pct / 100).toFixed(1)) : '-'">
                        </span>
                    </div>
                </div>

                <!-- Rating Category Options (Baik / Cukup / Kurang) -->
                <div class="grid grid-cols-3 gap-2">
                    @foreach($rubric->ratingOptions as $option)
                    @php
                        $val = (float) $option->numeric_value;
                        $label = $option->label;
                    @endphp
                    <button type="button" 
                            @click="selectOption('{{ $criterion->id }}', {{ $option->id }}, {{ $val }})"
                            :disabled="!{{ $canEdit ? 'true' : 'false' }}"
                            :class="scores['{{ $criterion->id }}'].rating_option_id === {{ $option->id }} ? 
                                '{{ str_contains(strtolower($label), 'baik') ? 'bg-emerald-600 text-white shadow-md border-emerald-600' : (str_contains(strtolower($label), 'cukup') ? 'bg-amber-500 text-white shadow-md border-amber-500' : 'bg-rose-500 text-white shadow-md border-rose-500') }} ring-2 ring-offset-1' : 
                                'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                            class="py-3 px-2 rounded-2xl border text-center transition-all active:scale-95 disabled:opacity-60 flex flex-col items-center justify-center">
                        <span class="text-xs font-bold block">{{ $label }}</span>
                        <span class="text-[11px] font-mono opacity-80">({{ $val }})</span>
                    </button>
                    @endforeach
                </div>

                <!-- Custom Numeric Score Slider & Input (Min 50 - Max 100 sesuai Form Juri) -->
                <div class="pt-1 flex items-center space-x-3 bg-slate-50 px-3 py-2 rounded-2xl border border-slate-200">
                    <span class="text-[11px] font-bold text-slate-600 shrink-0">Nilai (50–100):</span>
                    <input type="range" min="50" max="100" step="1"
                           :value="scores['{{ $criterion->id }}'].numeric_value ?? 75"
                           @input="setNumericValue('{{ $criterion->id }}', $event.target.value)"
                           :disabled="!{{ $canEdit ? 'true' : 'false' }}"
                           class="flex-1 accent-purple-700 cursor-pointer disabled:opacity-60">
                    <input type="number" min="50" max="100" step="1"
                           :value="scores['{{ $criterion->id }}'].numeric_value"
                           @input="scores['{{ $criterion->id }}'].rating_option_id = null; scores['{{ $criterion->id }}'].numeric_value = $event.target.value !== '' ? parseFloat($event.target.value) : null"
                           @blur="setNumericValue('{{ $criterion->id }}', $event.target.value)"
                           :disabled="!{{ $canEdit ? 'true' : 'false' }}"
                           placeholder="50-100"
                           class="w-16 px-2 py-1 text-xs font-mono font-bold text-center bg-white border border-slate-300 rounded-xl focus:ring-1 focus:ring-purple-600">
                </div>

                <!-- Hidden inputs for form submit -->
                <input type="hidden" name="scores[{{ $criterion->id }}][rating_option_id]" :value="scores['{{ $criterion->id }}'].rating_option_id ?? ''">
                <input type="hidden" name="scores[{{ $criterion->id }}][numeric_value]" :value="scores['{{ $criterion->id }}'].numeric_value ?? ''">

                <!-- Optional Comment per criterion -->
                <div class="pt-1">
                    <input type="text" 
                           name="scores[{{ $criterion->id }}][comment]" 
                           x-model="scores['{{ $criterion->id }}'].comment"
                           :disabled="!{{ $canEdit ? 'true' : 'false' }}"
                           placeholder="Catatan kriteria ini (opsional)" 
                           class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>
            @endforeach
        </div>

        <!-- General Judge Notes -->
        <div class="mt-4 bg-white rounded-3xl p-4 border border-slate-200 shadow-xs">
            <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                Catatan Umum Juri (Opsional)
            </label>
            <textarea id="notes" name="notes" rows="2" 
                      x-model="notes"
                      :disabled="!{{ $canEdit ? 'true' : 'false' }}"
                      placeholder="Masukkan catatan penilaian atau saran untuk peserta..." 
                      class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
        </div>

        <!-- Sticky Floating Bottom Scoring Action Bar -->
        <div class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-slate-200 p-3 z-50 shadow-2xl">
            <div class="max-w-2xl mx-auto flex items-center justify-between space-x-3">
                <div class="shrink-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block leading-tight">Total Nilai</span>
                    <span class="text-2xl font-black text-emerald-800 font-mono" x-text="totalScore">0.0</span>
                    <span class="text-[10px] text-slate-400">/ 100</span>
                </div>

                @if($canEdit)
                <div class="flex items-center space-x-2 flex-1 justify-end">
                    <button type="submit" @click="handleSubmit($event, 'draft')"
                            class="py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                        Draft
                    </button>
                    <button type="submit" @click="handleSubmit($event, 'submit')"
                            :disabled="!isFormValid()"
                            class="py-3 px-5 rounded-xl bg-emerald-700 hover:bg-emerald-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-extrabold text-xs shadow-md shadow-emerald-700/30 transition-all active:scale-98">
                        Submit Nilai Resmi
                    </button>
                </div>
                @else
                <div>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1.5 rounded-xl">
                        Mode Hanya Lihat
                    </span>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection
