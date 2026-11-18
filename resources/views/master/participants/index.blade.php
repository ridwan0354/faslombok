@extends('layouts.master')

@section('title', 'Data Peserta & Kelompok - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{
    showAddModal: false, 
    showImportModal: false,
    showEditModal: false,
    showGroupsModal: false,
    showGradesModal: false,
    allCompetitions: {{ json_encode($competitions->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'grade_ids' => $c->gradeLevels->pluck('id')->values()->all()
    ])->values()->all()) }},
    selectedGradeId: '',
    selectedCompIds: [],
    get filteredCompetitionsAdd() {
        if (!this.selectedGradeId) return [];
        const gid = parseInt(this.selectedGradeId);
        return this.allCompetitions.filter(c => c.grade_ids.length === 0 || c.grade_ids.includes(gid));
    },
    editData: {
        id: '',
        name: '',
        group_id: '',
        grade_id: '',
        gender: 'L',
        active: true,
        competitions: []
    },
    get filteredCompetitionsEdit() {
        if (!this.editData.grade_id) return this.allCompetitions;
        const gid = parseInt(this.editData.grade_id);
        return this.allCompetitions.filter(c => c.grade_ids.length === 0 || c.grade_ids.includes(gid) || this.editData.competitions.includes(c.id));
    },
    openAdd() {
        this.selectedGradeId = '';
        this.selectedCompIds = [];
        this.showAddModal = true;
    },
    openEdit(p) {
        this.editData = {
            id: p.id,
            name: p.name,
            group_id: p.group_id,
            grade_id: p.grade_id ? String(p.grade_id) : '',
            gender: p.gender || 'L',
            active: p.active ? true : false,
            competitions: p.entries ? p.entries.map(e => e.competition_id) : []
        };
        this.showEditModal = true;
    }
}">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-purple-950 tracking-tight">Data Peserta & Kelompok / TPQ</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola data peserta, kelompok / TPQ, tingkat kelas, dan impor massal CSV.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button @click="showGroupsModal = true" 
                    class="px-3.5 py-2.5 rounded-xl bg-white border border-purple-200 text-purple-900 font-bold text-xs hover:bg-purple-50 transition-colors shadow-xs flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Kelola Kelompok / TPQ</span>
            </button>
            <button @click="showGradesModal = true" 
                    class="px-3.5 py-2.5 rounded-xl bg-white border border-pink-200 text-pink-900 font-bold text-xs hover:bg-pink-50 transition-colors shadow-xs flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-pink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>Kelola Tingkat Kelas</span>
            </button>
            <a href="{{ route('master.print.qr') }}" 
               class="px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors shadow-xs flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span>Cetak Badge QR</span>
            </a>
            <button @click="showImportModal = true"
                    class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition-colors flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Impor CSV / Excel</span>
            </button>
            <button @click="openAdd()"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-800 to-pink-700 text-white font-bold text-xs hover:from-purple-900 hover:to-pink-800 transition-colors shadow-sm flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Peserta</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
        <form action="{{ route('master.participants') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode peserta..." 
                       class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 focus:outline-none">
            </div>
            <div>
                <select name="group_id" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                    <option value="">Semua Kelompok / TPQ</option>
                    @foreach($groups as $grp)
                        <option value="{{ $grp->id }}" {{ request('group_id') == $grp->id ? 'selected' : '' }}>{{ $grp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="flex-1 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                <a href="{{ route('master.participants') }}" class="py-2 px-3 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200 transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Participants Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Kode</th>
                        <th class="py-3 px-4">Nama Peserta</th>
                        <th class="py-3 px-4 text-center">L/P</th>
                        <th class="py-3 px-4">Kelompok / TPQ</th>
                        <th class="py-3 px-4">Tingkat Kelas</th>
                        <th class="py-3 px-4">Cabang Lomba Diikuti</th>
                        <th class="py-3 px-4 text-center">Status QR</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($participants as $p)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-4 font-mono font-bold text-emerald-800 text-xs">
                            {{ $p->participant_code }}
                        </td>
                        <td class="py-3.5 px-4 font-bold text-slate-900">
                            {{ $p->name }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $p->gender === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                {{ $p->gender }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 font-medium">
                            {{ $p->group ? $p->group->name : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 font-medium">
                            {{ $p->gradeLevel ? $p->gradeLevel->name : '-' }}
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($p->entries as $entry)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $entry->competition->name }}
                                    </span>
                                @empty
                                    <span class="text-slate-400 text-xs">Belum ada lomba</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($p->qrToken && $p->qrToken->isValid())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    Aktif
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end space-x-1.5">
                                <button type="button" 
                                        @click="openEdit({{ json_encode([
                                            'id' => $p->id,
                                            'name' => $p->name,
                                            'group_id' => $p->group_id,
                                            'grade_id' => $p->grade_id,
                                            'gender' => $p->gender,
                                            'active' => $p->active,
                                            'entries' => $p->entries->map(fn($e) => ['competition_id' => $e->competition_id]),
                                        ]) }})"
                                        class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs border border-emerald-200 transition-colors flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Edit</span>
                                </button>
                                <form action="{{ route('master.participants.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus peserta {{ $p->name }}?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            Tidak ada data peserta yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $participants->links() }}
        </div>
    </div>

    <!-- Modal Tambah Peserta Manual -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4" @click.away="showAddModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Pendaftaran Peserta Baru</h3>
                <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('master.participants.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap Peserta</label>
                    <input type="text" name="name" required placeholder="Contoh: Muhammad Ali" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Kelompok / TPQ</label>
                            <button type="button" @click="showAddModal = false; showGroupsModal = true" class="text-[10px] font-bold text-emerald-700 hover:underline">
                                + Kelola
                            </button>
                        </div>
                        <select name="group_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jenis Kelamin</label>
                        <select name="gender" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Tingkat Kelas</label>
                        <button type="button" @click="showAddModal = false; showGradesModal = true" class="text-[10px] font-bold text-indigo-700 hover:underline">
                            + Kelola
                        </button>
                    </div>
                    <select name="grade_id" x-model="selectedGradeId" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                        <option value="">-- Pilih Tingkat Kelas --</option>
                        @foreach($grades as $gr)
                            <option value="{{ $gr->id }}">{{ $gr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Cabang Lomba Diikuti</label>
                        <template x-if="selectedGradeId && filteredCompetitionsAdd.length > 0">
                            <span class="text-[11px] text-emerald-700 font-semibold" x-text="'(' + filteredCompetitionsAdd.length + ' lomba memenuhi syarat)'"></span>
                        </template>
                    </div>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                        <template x-if="!selectedGradeId">
                            <p class="text-xs text-slate-400 italic py-2 text-center">
                                Silakan pilih Tingkat Kelas di atas terlebih dahulu untuk menampilkan cabang lomba yang berhak diikuti santri.
                            </p>
                        </template>
                        <template x-if="selectedGradeId && filteredCompetitionsAdd.length === 0">
                            <p class="text-xs text-amber-600 font-medium py-2 text-center">
                                Belum ada cabang lomba individu yang dibuka untuk tingkat kelas ini.
                            </p>
                        </template>
                        <template x-for="c in filteredCompetitionsAdd" :key="c.id">
                            <label class="flex items-center space-x-2 py-1 px-1.5 rounded-lg hover:bg-white cursor-pointer transition-colors">
                                <input type="checkbox" name="competitions[]" :value="c.id" x-model="selectedCompIds" class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="text-slate-800 font-medium" x-text="c.name"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-purple-800 to-pink-700 hover:from-purple-900 hover:to-pink-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Peserta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Data Peserta -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4" @click.away="showEditModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Edit Data Peserta</h3>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'/master/peserta/' + editData.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap Peserta</label>
                    <input type="text" name="name" x-model="editData.name" required placeholder="Nama Peserta" class="w-full px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Kelompok / TPQ</label>
                            <button type="button" @click="showEditModal = false; showGroupsModal = true" class="text-[10px] font-bold text-emerald-700 hover:underline">
                                + Kelola
                            </button>
                        </div>
                        <select name="group_id" x-model="editData.group_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Jenis Kelamin</label>
                        <select name="gender" x-model="editData.gender" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Tingkat Kelas</label>
                        <button type="button" @click="showEditModal = false; showGradesModal = true" class="text-[10px] font-bold text-indigo-700 hover:underline">
                            + Kelola
                        </button>
                    </div>
                    <select name="grade_id" x-model="editData.grade_id" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                        <option value="">-- Pilih Tingkat Kelas --</option>
                        @foreach($grades as $gr)
                            <option value="{{ $gr->id }}">{{ $gr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Cabang Lomba Diikuti</label>
                        <template x-if="editData.grade_id">
                            <span class="text-[11px] text-emerald-700 font-semibold" x-text="'(' + filteredCompetitionsEdit.length + ' lomba tersedia)'"></span>
                        </template>
                    </div>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                        <template x-for="c in filteredCompetitionsEdit" :key="c.id">
                            <label class="flex items-center space-x-2 py-1 px-1.5 rounded-lg hover:bg-white cursor-pointer transition-colors">
                                <input type="checkbox" name="competitions[]" :value="c.id" :checked="editData.competitions.includes(c.id)" @change="
                                    if ($event.target.checked) {
                                        if (!editData.competitions.includes(c.id)) editData.competitions.push(c.id);
                                    } else {
                                        editData.competitions = editData.competitions.filter(id => id !== c.id);
                                    }
                                " class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="text-slate-800 font-medium" x-text="c.name"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="flex items-center space-x-2 text-xs text-slate-700 font-medium">
                        <input type="checkbox" name="active" value="1" x-model="editData.active" class="rounded text-emerald-600">
                        <span>Status Peserta Aktif</span>
                    </label>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-purple-800 to-pink-700 hover:from-purple-900 hover:to-pink-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Kelola Kelompok / TPQ -->
    <div x-show="showGroupsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-xl w-full shadow-2xl space-y-5" @click.away="showGroupsModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Kelola Kelompok / TPQ</h3>
                    <p class="text-xs text-slate-500">Tambah, ubah, atau hapus nama kelompok / TPQ peserta.</p>
                </div>
                <button @click="showGroupsModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Form Tambah Kelompok Baru -->
            <form action="{{ route('master.groups.store') }}" method="POST" class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-3">
                @csrf
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-950 block">+ Tambah Kelompok / TPQ Baru</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div class="sm:col-span-2">
                        <input type="text" name="name" required placeholder="Nama Kelompok / TPQ (contoh: Ampenan, Mataram)" 
                               class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600">
                    </div>
                    <div>
                        <input type="text" name="code" placeholder="Kode (misal: AMP)" 
                               class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 uppercase">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                        Simpan Kelompok
                    </button>
                </div>
            </form>

            <!-- Daftar Kelompok yang Ada -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-2">Daftar Kelompok / TPQ Terdaftar ({{ count($groups) }}):</span>
                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    @forelse($groups as $grp)
                    <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900 block">{{ $grp->name }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $grp->code }} • {{ $grp->participants->count() }} Peserta</span>
                        </div>
                        <form action="{{ route('master.groups.destroy', $grp->id) }}" method="POST" onsubmit="return confirm('Hapus kelompok {{ $grp->name }}?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 py-3 text-center">Belum ada kelompok / TPQ yang terdaftar.</p>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" @click="showGroupsModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Selesai
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Kelola Tingkat Kelas -->
    <div x-show="showGradesModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-xl w-full shadow-2xl space-y-5" @click.away="showGradesModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Kelola Tingkat Kelas</h3>
                    <p class="text-xs text-slate-500">Tambah, ubah, atau hapus pilihan tingkat kelas peserta.</p>
                </div>
                <button @click="showGradesModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Form Tambah Tingkat Kelas Baru -->
            <form action="{{ route('master.grade-levels.store') }}" method="POST" class="p-3.5 bg-indigo-50/70 border border-indigo-200 rounded-2xl space-y-3">
                @csrf
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-950 block">+ Tambah Tingkat Kelas Baru</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div class="sm:col-span-2">
                        <input type="text" name="name" required placeholder="Nama Tingkat Kelas (contoh: Kelas 3–4 SD)" 
                               class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-xl focus:ring-1 focus:ring-indigo-600">
                    </div>
                    <div>
                        <input type="text" name="code" placeholder="Kode (misal: KELAS_3_4)" 
                               class="w-full px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-xl focus:ring-1 focus:ring-indigo-600 uppercase">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-1.5 bg-indigo-700 hover:bg-indigo-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                        Simpan Tingkat Kelas
                    </button>
                </div>
            </form>

            <!-- Daftar Tingkat Kelas yang Ada -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-2">Daftar Tingkat Kelas ({{ count($grades) }}):</span>
                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    @forelse($grades as $gr)
                    <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900 block">{{ $gr->name }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $gr->code }} • Urutan #{{ $gr->sort_order }}</span>
                        </div>
                        <form action="{{ route('master.grade-levels.destroy', $gr->id) }}" method="POST" onsubmit="return confirm('Hapus tingkat kelas {{ $gr->name }}?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 py-3 text-center">Belum ada tingkat kelas yang terdaftar.</p>
                    @endforelse
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" @click="showGradesModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Selesai
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Impor CSV / Excel -->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4" @click.away="showImportModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900">Impor Peserta Massal (CSV)</h3>
                <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-950 space-y-1">
                <p class="font-bold">Format Kolom CSV:</p>
                <p class="font-mono text-[11px] text-emerald-800">nama, kelompok, kelas, jenis_kelamin, lomba</p>
                <p class="text-[11px] text-emerald-700">Setiap peserta otomatis dibuatkan nomor urut, kode peserta, dan token QR unik.</p>
            </div>

            <form action="{{ route('master.participants.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Berkas CSV</label>
                    <input type="file" name="file" accept=".csv,text/csv,text/plain" required
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs">Proses Impor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
