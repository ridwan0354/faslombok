@extends('layouts.master')

@section('title', "Detail Lomba: {$competition->name} - FAS Lombok 2026")

@section('content')
@php
    $rubric = $competition->activeRubric;
    $initialCriteria = $rubric ? $rubric->criteria->map(function($c) {
        return ['title' => $c->title, 'weight_pct' => (float) $c->weight_pct];
    })->toArray() : [];
@endphp

<div class="space-y-6" x-data="{
    criteria: {{ json_encode($initialCriteria) }},
    get totalWeight() {
        return this.criteria.reduce((sum, c) => sum + (parseFloat(c.weight_pct) || 0), 0);
    },
    addCriterion() {
        this.criteria.push({ title: '', weight_pct: 0 });
    },
    removeCriterion(index) {
        this.criteria.splice(index, 1);
    }
}">
    <!-- Competition Header Bar -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('master.competitions') }}" class="text-xs font-semibold text-emerald-700 flex items-center space-x-1 mb-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Daftar Cabang Lomba</span>
            </a>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $competition->format === 'team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                    {{ $competition->format === 'team' ? 'Beregu' : 'Individu' }}
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-snug">{{ $competition->name }}</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">Syarat Tingkat Kelas: <span class="font-semibold text-slate-700">{{ $competition->eligibility_rule ?? 'Semua Tingkat Kelas' }}</span></p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Lock / Unlock Form -->
            <form action="{{ route('master.competitions.lock', $competition->id) }}" method="POST">
                @csrf
                <button type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold border transition-colors flex items-center space-x-1.5 {{ $competition->is_locked ? 'bg-amber-50 border-amber-300 text-amber-900 hover:bg-amber-100' : 'bg-slate-50 border-slate-300 text-slate-700 hover:bg-slate-100' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>{{ $competition->is_locked ? 'Buka Kunci Nilai' : 'Kunci Penilaian' }}</span>
                </button>
            </form>

            <!-- Publish Form -->
            <form action="{{ route('master.competitions.publish', $competition->id) }}" method="POST" onsubmit="return confirm('Kunci dan publikasikan hasil resmi perlombaan ini ke halaman publik?')">
                @csrf
                <button type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-700 text-white hover:bg-emerald-800 shadow-sm flex items-center space-x-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $competition->status === 'published' ? 'Perbarui Publikasi' : 'Kunci & Publikasikan' }}</span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Rubric Criteria Editor (100% Weight Enforced) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Rubrik Penilaian (Versi {{ $rubric ? $rubric->version : 1 }})</h3>
                    <p class="text-xs text-slate-500">Bobot total seluruh kriteria wajib berjumlah tepat 100%.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-mono font-black px-2.5 py-1 rounded-lg"
                          :class="Math.round(totalWeight) === 100 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                        Total: <span x-text="totalWeight"></span>%
                    </span>
                </div>
            </div>

            <form action="{{ route('master.competitions.rubric.update', $competition->id) }}" method="POST" class="space-y-3">
                @csrf
                <div class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
                    <template x-for="(crit, index) in criteria" :key="index">
                        <div class="flex items-center space-x-2 bg-slate-50 p-2.5 rounded-2xl border border-slate-200">
                            <span class="w-6 text-center text-xs font-bold text-slate-400" x-text="index + 1"></span>
                            <input type="text" :name="'criteria['+index+'][title]'" x-model="crit.title" required
                                   placeholder="Judul Kriteria (contoh: Makhraj & Tajwid)" 
                                   class="flex-1 px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 focus:outline-none font-medium">
                            <div class="w-24 relative flex items-center">
                                <input type="number" step="0.5" :name="'criteria['+index+'][weight_pct]'" x-model.number="crit.weight_pct" required
                                       class="w-full pl-2 pr-6 py-1.5 text-xs bg-white border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 focus:outline-none font-mono font-bold text-right">
                                <span class="absolute right-2 text-xs text-slate-400 font-bold">%</span>
                            </div>
                            <button type="button" @click="removeCriterion(index)" class="p-1 text-slate-400 hover:text-rose-600 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <button type="button" @click="addCriterion()" 
                            class="px-3 py-1.5 rounded-xl border border-dashed border-slate-300 hover:border-emerald-500 text-xs font-bold text-slate-600 hover:text-emerald-700 transition-colors">
                        + Tambah Baris Kriteria
                    </button>
                    <button type="submit" :disabled="Math.round(totalWeight) !== 100"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-bold shadow-xs transition-colors">
                        Simpan Rubrik
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-4 border-t border-slate-100">
                <span class="text-xs font-bold text-slate-700 block mb-2">Konversi Angka Kategori Rubrik Aktif:</span>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($rubric ? $rubric->ratingOptions : [] as $opt)
                    <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-center">
                        <span class="text-xs font-bold text-slate-800 block">{{ $opt->label }}</span>
                        <span class="text-[11px] font-mono text-emerald-700 font-bold">{{ (float)$opt->numeric_value }} Poin</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Judges & Granular Permissions Assignment -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Penugasan & Izin Dewan Juri</h3>
                <p class="text-xs text-slate-500">Atur hak nilai, hak melihat nilai juri sejawat, dan status juri wajib.</p>
            </div>

            <!-- Existing Assignments -->
            <div class="space-y-3">
                @forelse($competition->judgeAssignments as $ja)
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-900">{{ $ja->user->name }}</h4>
                        <span class="text-[11px] text-slate-500 font-mono">{{ $ja->user->phone }}</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <span class="px-2 py-0.2 rounded-md text-[9px] font-bold {{ $ja->can_score ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $ja->can_score ? 'Bisa Menilai' : 'View Only' }}
                            </span>
                            @if($ja->can_view_peer_scores)
                                <span class="px-2 py-0.2 rounded-md text-[9px] font-semibold bg-indigo-100 text-indigo-800">
                                    Lihat Juri Sejawat
                                </span>
                            @endif
                            @if($ja->required_for_completion)
                                <span class="px-2 py-0.2 rounded-md text-[9px] font-semibold bg-amber-100 text-amber-800">
                                    Juri Wajib
                                </span>
                            @endif
                        </div>
                    </div>

                    <form action="{{ route('master.judges.assign.revoke', $ja->id) }}" method="POST" onsubmit="return confirm('Cabut penugasan juri ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 p-1">
                            Cabut
                        </button>
                    </form>
                </div>
                @empty
                <div class="py-6 text-center text-xs text-slate-400">
                    Belum ada juri yang ditugaskan ke cabang lomba ini.
                </div>
                @endforelse
            </div>

            <!-- Form Assign New Judge -->
            <form action="{{ route('master.judges.assign') }}" method="POST" class="pt-3 border-t border-slate-100 space-y-3">
                @csrf
                <input type="hidden" name="competition_id" value="{{ $competition->id }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Juri</label>
                    <select name="user_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                        <option value="">-- Pilih Akun Juri --</option>
                        @foreach($availableJudges as $aj)
                            <option value="{{ $aj->id }}">{{ $aj->name }} ({{ $aj->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5 text-xs text-slate-700 font-medium">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="can_score" value="1" checked class="rounded text-emerald-600">
                        <span>Izinkan Menilai (Can Score)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="can_view_peer_scores" value="1" class="rounded text-emerald-600">
                        <span>Boleh Melihat Nilai Juri Lain (Can View Peer Scores)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="required_for_completion" value="1" checked class="rounded text-emerald-600">
                        <span>Juri Wajib untuk Nilai Lengkap (Required)</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-colors">
                    Tugaskan Juri
                </button>
            </form>
        </div>
    </div>

    <!-- Registered Entries List -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Daftar Peserta Terdaftar ({{ $competition->entries->count() }} Entri)</h3>
                <p class="text-xs text-slate-500">Anda dapat mengeluarkan peserta dari cabang lomba ini atau mengedit datanya di halaman Peserta.</p>
            </div>
            <a href="{{ route('master.participants', ['competition_id' => $competition->id]) }}"
               class="px-3.5 py-2 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-900 font-bold text-xs border border-purple-200 transition-colors self-start">
                Edit di Menu Peserta →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-3">No. Urut</th>
                        <th class="py-2.5 px-3">Nama Peserta</th>
                        <th class="py-2.5 px-3">Kelompok / TPQ</th>
                        <th class="py-2.5 px-3">Kelas</th>
                        <th class="py-2.5 px-3 text-center">Status Nilai</th>
                        <th class="py-2.5 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($competition->entries->sortBy('queue_number') as $ent)
                    <tr class="hover:bg-slate-50">
                        <td class="py-2.5 px-3 font-mono font-bold text-slate-500">#{{ $ent->queue_number ?? '-' }}</td>
                        <td class="py-2.5 px-3 font-bold text-slate-900">
                            {{ $ent->display_name }}
                            <span class="block text-[10px] font-mono text-slate-400 font-normal">{{ $ent->entity_code }}</span>
                        </td>
                        <td class="py-2.5 px-3 text-slate-700">{{ $ent->group ? $ent->group->name : '-' }}</td>
                        <td class="py-2.5 px-3 text-slate-600">{{ $ent->participant && $ent->participant->gradeLevel ? $ent->participant->gradeLevel->name : '-' }}</td>
                        <td class="py-2.5 px-3 text-center">
                            @if($ent->scoreSubmissions->isNotEmpty())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    {{ $ent->scoreSubmissions->count() }} Nilai Masuk
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500">
                                    Belum Dinilai
                                </span>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-right">
                            <form action="{{ route('master.competitions.entries.destroy', [$competition->id, $ent->id]) }}" method="POST"
                                  data-confirm="Keluarkan peserta {{ $ent->display_name }} dari lomba {{ $competition->name }}?"
                                  onsubmit="return confirm(this.dataset.confirm)" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[11px] border border-rose-200 transition-colors">
                                    Keluarkan
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-400">Belum ada peserta terdaftar pada lomba ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
