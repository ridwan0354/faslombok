@extends('layouts.master')

@section('title', 'Master Dashboard - FAS Lombok 2026')

@section('content')
<div class="space-y-6">
    <!-- Header with Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Overview Acara</h1>
            <p class="text-xs text-slate-500 mt-1">Festival Anak Sholeh Lombok 2026 • Monitoring Terpadu</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('master.print.qr') }}" 
               class="px-3.5 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-colors shadow-xs flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Badge QR</span>
            </a>
            <a href="{{ route('master.participants') }}" 
               class="px-3.5 py-2 rounded-xl bg-emerald-700 text-white font-bold text-xs hover:bg-emerald-800 transition-colors shadow-sm flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Kelola Peserta & Impor</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Peserta Terdaftar</span>
            <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ $stats['total_participants'] }}</div>
            <span class="text-[11px] text-emerald-600 font-medium">Dalam {{ $stats['total_groups'] }} Kafilah / TPQ</span>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Cabang Perlombaan</span>
            <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ $stats['total_competitions'] }}</div>
            <span class="text-[11px] text-amber-600 font-medium">{{ $stats['published_competitions'] }} Hasil Diumumkan</span>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Dewan Juri</span>
            <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ $stats['total_judges'] }}</div>
            <span class="text-[11px] text-indigo-600 font-medium">Multi-assignment Aktif</span>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Progres Penilaian</span>
            <div class="text-2xl font-black text-emerald-800 mt-1 font-mono">
                {{ $stats['total_entries'] > 0 ? round(($stats['completed_entries'] / $stats['total_entries']) * 100) : 0 }}%
            </div>
            <span class="text-[11px] text-slate-500 font-medium">{{ $stats['completed_entries'] }} dari {{ $stats['total_entries'] }} Selesai</span>
        </div>
    </div>

    <!-- Active Competitions Status Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Status 12 Cabang Perlombaan</h3>
                <p class="text-xs text-slate-500">Pantau progres penilaian juri, status kunci, dan publikasi juara.</p>
            </div>
            <a href="{{ route('master.competitions') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">
                Kelola Semua Lomba →
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Nama Lomba</th>
                        <th class="py-3 px-4">Format</th>
                        <th class="py-3 px-4">Dewan Juri</th>
                        <th class="py-3 px-4 text-center">Peserta</th>
                        <th class="py-3 px-4 text-center">Status Nilai</th>
                        <th class="py-3 px-4 text-center">Publikasi</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @foreach($competitions as $comp)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-4 font-bold text-slate-900">
                            {{ $comp->name }}
                            <span class="block text-[11px] text-slate-400 font-normal">{{ $comp->location ?? 'Panggung Utama' }}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $comp->format === 'team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $comp->format === 'team' ? 'Beregu' : 'Individu' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-600">
                            {{ $comp->judgeAssignments->pluck('user.name')->implode(', ') ?: 'Belum ada juri' }}
                        </td>
                        <td class="py-3.5 px-4 text-center font-mono font-bold">
                            {{ $comp->entries_count }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($comp->is_locked)
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900">
                                    Terkunci
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                    Terbuka
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($comp->status === 'published')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                    Published (v{{ $comp->latestPublication ? $comp->latestPublication->version : 1 }})
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500">
                                    Draft
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <a href="{{ route('master.competitions.detail', $comp->id) }}" 
                               class="text-xs font-bold text-emerald-700 hover:text-emerald-900">
                                Konfigurasi & Rubrik →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-5">
        <h3 class="text-base font-bold text-slate-900 mb-1">Aktivitas & Log Audit Nilai Terkini</h3>
        <p class="text-xs text-slate-500 mb-4">Setiap tindakan penilaian, perubahan rubrik, dan publikasi terekam lengkap.</p>

        <div class="space-y-2">
            @forelse($recentAudits as $log)
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-3">
                    <span class="w-2 h-2 rounded-full {{ $log->action === 'publish' ? 'bg-amber-500' : ($log->action === 'lock' ? 'bg-rose-500' : 'bg-emerald-500') }}"></span>
                    <div>
                        <span class="font-bold text-slate-900">{{ $log->actor ? $log->actor->name : 'Sistem' }}</span>
                        <span class="text-slate-500 font-medium">— {{ ucfirst($log->action) }} ({{ $log->reason ?? '-' }})</span>
                    </div>
                </div>
                <span class="text-[11px] text-slate-400 font-mono">
                    {{ $log->created_at ? $log->created_at->setTimezone('Asia/Makassar')->format('d M H:i WITA') : '-' }}
                </span>
            </div>
            @empty
            <p class="text-xs text-slate-400 py-3 text-center">Belum ada riwayat audit terbaru.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
