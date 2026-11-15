@extends('layouts.master')

@section('title', 'Manajemen Antrean Panggung - FAS Lombok 2026')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Antrean Panggung & Pemanggilan</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola urutan tampil dan status peserta (Antre, Dipanggil, Tampil, Selesai, Diskualifikasi, Tidak Hadir).</p>
        </div>

        <!-- Competition Selector Dropdown -->
        <div class="flex items-center space-x-2">
            <label class="text-xs font-bold text-slate-600 uppercase">Pilih Lomba:</label>
            <form action="{{ route('master.queue') }}" method="GET" class="inline-block">
                <select name="competition_id" onchange="this.form.submit()" 
                        class="px-3.5 py-2 text-xs border border-slate-300 rounded-xl focus:ring-1 focus:ring-emerald-600 font-bold text-slate-800 bg-white">
                    @foreach($competitions as $c)
                        <option value="{{ $c->id }}" {{ $selectedComp && $selectedComp->id === $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->entries->count() }} Peserta)
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($selectedComp)
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ $selectedComp->name }}</h3>
                <p class="text-xs text-slate-500">Lokasi: {{ $selectedComp->location ?? 'Panggung Utama' }} • Format: {{ ucfirst($selectedComp->format) }}</p>
            </div>
            <span class="text-xs font-mono font-bold text-slate-600">Total: {{ $entries->count() }} Antrean</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4 text-center w-16">No. Urut</th>
                        <th class="py-3 px-4">Nama Peserta / Tim</th>
                        <th class="py-3 px-4">Kelompok / TPQ</th>
                        <th class="py-3 px-4 text-center">Status Tampil</th>
                        <th class="py-3 px-4 text-right">Ubah Status Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($entries as $entry)
                    <tr class="hover:bg-slate-50 transition-colors {{ $entry->status === 'performing' ? 'bg-amber-50/50' : ($entry->status === 'called' ? 'bg-blue-50/30' : '') }}">
                        <td class="py-3.5 px-4 text-center font-mono font-black text-sm text-slate-800">
                            #{{ $entry->queue_number ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-slate-900 block">{{ $entry->display_name }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $entry->entity_code }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600">
                            {{ $entry->group ? $entry->group->name : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @php
                                $badgeClasses = [
                                    'registered' => 'bg-slate-100 text-slate-700',
                                    'queued' => 'bg-blue-100 text-blue-800',
                                    'called' => 'bg-indigo-100 text-indigo-800 animate-pulse',
                                    'performing' => 'bg-amber-100 text-amber-900 font-bold ring-2 ring-amber-400',
                                    'completed' => 'bg-emerald-100 text-emerald-800',
                                    'disqualified' => 'bg-rose-100 text-rose-800',
                                    'no_show' => 'bg-slate-200 text-slate-600',
                                ];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $badgeClasses[$entry->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end space-x-1">
                                <form action="{{ route('master.queue.status', $entry->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="called">
                                    <button type="submit" title="Panggil Peserta" class="px-2 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs">
                                        Panggil
                                    </button>
                                </form>
                                <form action="{{ route('master.queue.status', $entry->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="performing">
                                    <button type="submit" title="Mulai Tampil" class="px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs">
                                        Tampil
                                    </button>
                                </form>
                                <form action="{{ route('master.queue.status', $entry->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" title="Tandai Selesai" class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs">
                                        Selesai
                                    </button>
                                </form>
                                <form action="{{ route('master.queue.status', $entry->id) }}" method="POST" class="inline" onsubmit="return confirm('Tandai tidak hadir?')">
                                    @csrf
                                    <input type="hidden" name="status" value="no_show">
                                    <button type="submit" title="Tidak Hadir" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs">
                                        No-Show
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400">
                            Belum ada peserta dalam antrean cabang lomba ini.
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
