@extends('layouts.master')

@section('title', 'Cabang Lomba & Rubrik - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{ 
    showAddModal: false, 
    showEditModal: false, 
    editCompData: { id: null, name: '', format: 'individual', grade_ids: [] },
    addGradeIds: [],
    allGradeIds: {{ json_encode($gradeLevels->pluck('id')->values()->all()) }},
    toggleAllAdd() {
        if (this.addGradeIds.length === this.allGradeIds.length) {
            this.addGradeIds = [];
        } else {
            this.addGradeIds = [...this.allGradeIds];
        }
    },
    toggleAllEdit() {
        if (this.editCompData.grade_ids.length === this.allGradeIds.length) {
            this.editCompData.grade_ids = [];
        } else {
            this.editCompData.grade_ids = [...this.allGradeIds];
        }
    },
    openEdit(comp) {
        this.editCompData = {
            id: comp.id,
            name: comp.name,
            format: comp.format,
            grade_ids: comp.grade_ids || []
        };
        this.showEditModal = true;
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-purple-950 tracking-tight">Cabang Perlombaan & Rubrik</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola format, syarat tingkat kelas peserta, kriteria rubrik bobot 100%, dan status publikasi.</p>
        </div>
        <button @click="showAddModal = true; addGradeIds = []"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-800 to-pink-700 text-white font-bold text-xs hover:from-purple-900 hover:to-pink-800 transition-colors shadow-sm flex items-center space-x-1.5 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Cabang Lomba</span>
        </button>
    </div>

    <!-- Competitions Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Nama Lomba</th>
                        <th class="py-3 px-4">Format</th>
                        <th class="py-3 px-4">Syarat Tingkat Kelas</th>
                        <th class="py-3 px-4 text-center">Peserta</th>
                        <th class="py-3 px-4 text-center">Rubrik</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @foreach($competitions as $comp)
                    @php
                        $compJson = json_encode([
                            'id' => $comp->id,
                            'name' => $comp->name,
                            'format' => $comp->format,
                            'grade_ids' => $comp->gradeLevels->pluck('id')->values()->all(),
                        ]);
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-4 font-bold text-slate-900">
                            {{ $comp->name }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $comp->format === 'team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $comp->format === 'team' ? 'Beregu' : 'Individu' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($comp->gradeLevels->isNotEmpty())
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach($comp->gradeLevels as $gl)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ $gl->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">
                                    Semua Tingkat Kelas
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center font-mono font-bold">
                            {{ $comp->entries_count }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($comp->activeRubric)
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $comp->activeRubric->isWeightValid() ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    v{{ $comp->activeRubric->version }} ({{ $comp->activeRubric->totalWeight() }}%)
                                </span>
                            @else
                                <span class="text-rose-500 font-bold text-xs">Belum ada</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($comp->status === 'published')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900">
                                    Published
                                </span>
                            @elseif($comp->is_locked)
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-200 text-slate-700">
                                    Locked
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                    Ready
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                            <button type="button" 
                                    @click='openEdit({!! $compJson !!})'
                                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                                Edit Syarat
                            </button>
                            <a href="{{ route('master.competitions.detail', $comp->id) }}" 
                               class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs border border-emerald-200 transition-colors inline-block">
                                Rubrik & Izin →
                            </a>
                            <form action="{{ route('master.competitions.destroy', $comp->id) }}" 
                                  method="POST" 
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus cabang lomba &quot;{{ addslashes($comp->name) }}&quot;?')" 
                                  class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-800 font-bold text-xs border border-rose-200 transition-colors"
                                        title="Hapus Cabang Lomba">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Lomba -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4" @click.away="showAddModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Tambah Cabang Perlombaan Baru</h3>
                <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('master.competitions.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Cabang Lomba</label>
                    <input type="text" name="name" required placeholder="Contoh: Pidato Bahasa Arab" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Format Peserta</label>
                    <select name="format" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none font-medium">
                        <option value="individual">Individu</option>
                        <option value="team">Beregu / Tim</option>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Syarat Tingkat Kelas yang Berhak Ikut</label>
                        <button type="button" @click="toggleAllAdd()" class="text-[10px] font-bold text-emerald-700 hover:underline">
                            <span x-text="addGradeIds.length === allGradeIds.length ? 'Batal Pilih Semua' : 'Pilih Semua Kelas'"></span>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-2">Pilih kelas yang boleh mendaftar pada lomba ini. *(Jika tidak ada yang dicentang, lomba terbuka untuk semua kelas)*.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs max-h-48 overflow-y-auto">
                        @foreach($gradeLevels as $gl)
                        <label class="flex items-center space-x-2 py-1 px-1.5 rounded-lg hover:bg-white cursor-pointer transition-colors">
                            <input type="checkbox" name="grade_ids[]" value="{{ $gl->id }}" x-model="addGradeIds" class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-slate-800 font-medium">{{ $gl->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-purple-800 to-pink-700 hover:from-purple-900 hover:to-pink-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Lomba</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Lomba & Syarat Kelas -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4" @click.away="showEditModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Edit Cabang Lomba & Syarat Kelas</h3>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'/master/lomba/' + editCompData.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Cabang Lomba</label>
                    <input type="text" name="name" x-model="editCompData.name" required class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Format Peserta</label>
                    <select name="format" x-model="editCompData.format" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none font-medium">
                        <option value="individual">Individu</option>
                        <option value="team">Beregu / Tim</option>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Syarat Tingkat Kelas yang Berhak Ikut</label>
                        <button type="button" @click="toggleAllEdit()" class="text-[10px] font-bold text-emerald-700 hover:underline">
                            <span x-text="editCompData.grade_ids.length === allGradeIds.length ? 'Batal Pilih Semua' : 'Pilih Semua Kelas'"></span>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-2">Centang tingkat kelas yang boleh mendaftar. *(Jika kosong, terbuka untuk semua kelas)*.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs max-h-48 overflow-y-auto">
                        @foreach($gradeLevels as $gl)
                        <label class="flex items-center space-x-2 py-1 px-1.5 rounded-lg hover:bg-white cursor-pointer transition-colors">
                            <input type="checkbox" name="grade_ids[]" value="{{ $gl->id }}" x-model="editCompData.grade_ids" class="rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-slate-800 font-medium">{{ $gl->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-purple-800 to-pink-700 hover:from-purple-900 hover:to-pink-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
