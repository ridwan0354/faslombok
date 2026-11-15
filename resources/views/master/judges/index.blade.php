@extends('layouts.master')

@section('title', 'Kelola Juri & Hak Akses - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{ showAddJudgeModal: false, showAssignModal: false, selectedJudgeId: '' }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Dewan Juri & Hak Akses</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola akun login juri (Nomor HP & Password) dan penugasan per cabang lomba.</p>
        </div>
        <div class="flex items-center space-x-2">
            <button @click="showAddJudgeModal = true"
                    class="px-4 py-2.5 rounded-xl bg-emerald-700 text-white font-bold text-xs hover:bg-emerald-800 transition-colors shadow-sm flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>Tambah Akun Juri</span>
            </button>
        </div>
    </div>

    <!-- Judges List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($judges as $j)
        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">{{ $j->name }}</h3>
                        <p class="text-xs text-emerald-800 font-mono font-bold mt-0.5">{{ $j->phone }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $j->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ ucfirst($j->status) }}
                    </span>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1.5">
                        Cabang Lomba Ditugaskan ({{ $j->judgeAssignments->count() }}):
                    </span>
                    <div class="space-y-1.5 max-h-36 overflow-y-auto">
                        @forelse($j->judgeAssignments as $ja)
                        <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800">{{ $ja->competition->name }}</span>
                                <div class="flex space-x-1 mt-0.5">
                                    <span class="text-[9px] font-semibold {{ $ja->can_score ? 'text-emerald-700' : 'text-slate-500' }}">
                                        {{ $ja->can_score ? '• Menilai' : '• View' }}
                                    </span>
                                    @if($ja->can_view_peer_scores)
                                        <span class="text-[9px] font-semibold text-indigo-700">• Peer View</span>
                                    @endif
                                </div>
                            </div>
                            <form action="{{ route('master.judges.assign.revoke', $ja->id) }}" method="POST" onsubmit="return confirm('Cabut tugas juri ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] text-rose-600 font-bold hover:text-rose-800">
                                    Cabut
                                </button>
                            </form>
                        </div>
                        @empty
                        <p class="text-xs text-slate-400">Belum ada tugas perlombaan.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100">
                <button type="button" @click="selectedJudgeId = '{{ $j->id }}'; showAssignModal = true"
                        class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                    + Tugaskan ke Lomba
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal Tambah Akun Juri -->
    <div x-show="showAddJudgeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4" @click.away="showAddJudgeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Tambah Akun Juri Baru</h3>
                <button @click="showAddJudgeModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('master.judges.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Juri</label>
                    <input type="text" name="name" required placeholder="Contoh: Ustadz M. Fauzi, M.Ag" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nomor HP (WhatsApp untuk Login)</label>
                    <input type="tel" name="phone" required placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password Awal</label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600">
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAddJudgeModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Akun Juri</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tugaskan Juri -->
    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4" @click.away="showAssignModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Tugaskan Juri ke Cabang Lomba</h3>
                <button @click="showAssignModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('master.judges.assign') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="user_id" :value="selectedJudgeId">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Cabang Lomba</label>
                    <select name="competition_id" required class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                        <option value="">-- Pilih Lomba --</option>
                        @foreach($competitions as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->format === 'team' ? 'Beregu' : 'Individu' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 text-xs text-slate-700 font-medium bg-slate-50 p-3 rounded-2xl border border-slate-200">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="can_score" value="1" checked class="rounded text-emerald-600">
                        <span>Hak Menilai (Can Score)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="can_view_peer_scores" value="1" class="rounded text-emerald-600">
                        <span>Boleh Melihat Nilai Juri Lain (Peer Visibility)</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="required_for_completion" value="1" checked class="rounded text-emerald-600">
                        <span>Juri Wajib (Required for final completion)</span>
                    </label>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAssignModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
