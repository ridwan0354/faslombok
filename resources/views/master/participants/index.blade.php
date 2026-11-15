@extends('layouts.master')

@section('title', 'Data Peserta & Impor - FAS Lombok 2026')

@section('content')
<div class="space-y-6" x-data="{ showAddModal: false, showImportModal: false }">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Data Peserta & Pendaftaran</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola data peserta, impor massal via CSV, dan pembuatan token QR unik.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
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
            <button @click="showAddModal = true"
                    class="px-4 py-2.5 rounded-xl bg-emerald-700 text-white font-bold text-xs hover:bg-emerald-800 transition-colors shadow-sm flex items-center space-x-1.5">
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
                    <option value="">Semua Kafilah / TPQ</option>
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
                        <th class="py-3 px-4">L/P</th>
                        <th class="py-3 px-4">Kafilah / TPQ</th>
                        <th class="py-3 px-4">Tingkat Kelas</th>
                        <th class="py-3 px-4">Cabang Lomba Diikuti</th>
                        <th class="py-3 px-4 text-center">Status QR</th>
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
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $p->group ? $p->group->name : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
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
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kafilah / TPQ</label>
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
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tingkat Kelas</label>
                    <select name="grade_id" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-medium">
                        <option value="">-- Pilih Tingkat Kelas --</option>
                        @foreach($grades as $gr)
                            <option value="{{ $gr->id }}">{{ $gr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Cabang Lomba Diikuti</label>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto p-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                        @foreach($competitions as $c)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="competitions[]" value="{{ $c->id }}" class="rounded text-emerald-600">
                            <span>{{ $c->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs">Simpan Peserta</button>
                </div>
            </form>
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
