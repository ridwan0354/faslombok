@extends('layouts.master')

@section('title', 'Cabang Lomba & Rubrik - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{ showAddModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Cabang Perlombaan & Rubrik</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola format, kriteria rubrik bobot 100%, dan status publikasi.</p>
        </div>
        <button @click="showAddModal = true"
                class="px-4 py-2.5 rounded-xl bg-emerald-700 text-white font-bold text-xs hover:bg-emerald-800 transition-colors shadow-sm flex items-center space-x-1.5 self-start">
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
                        <th class="py-3 px-4">Tingkat Kelas</th>
                        <th class="py-3 px-4">Lokasi / Panggung</th>
                        <th class="py-3 px-4 text-center">Peserta</th>
                        <th class="py-3 px-4 text-center">Rubrik</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @foreach($competitions as $comp)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-4 font-bold text-slate-900">
                            {{ $comp->name }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $comp->format === 'team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $comp->format === 'team' ? 'Beregu' : 'Individu' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $comp->eligibility_rule ?? 'Semua Tingkat' }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $comp->location ?? '-' }}
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
                        <td class="py-3.5 px-4 text-right">
                            <a href="{{ route('master.competitions.detail', $comp->id) }}" 
                               class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs border border-emerald-200 transition-colors">
                                Edit Rubrik & Izin →
                            </a>
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

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Format Peserta</label>
                        <select name="format" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none font-medium">
                            <option value="individual">Individu</option>
                            <option value="team">Beregu / Tim</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Syarat Kelas</label>
                        <input type="text" name="eligibility_rule" placeholder="Contoh: Kelas 3–6" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Lokasi / Panggung</label>
                    <input type="text" name="location" placeholder="Contoh: Panggung 2 / Aula A" class="w-full px-3.5 py-2.5 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Lomba</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
