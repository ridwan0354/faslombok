@extends('layouts.judge')

@section('title', 'Portal Juri - FAS Lombok 2026')

@section('content')
<div class="space-y-4 pt-2">
    <!-- Welcome Card -->
    <div class="bg-gradient-to-br from-emerald-800 to-emerald-950 rounded-3xl p-5 text-white shadow-md relative overflow-hidden">
        <div class="relative z-10">
            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-400 text-emerald-950 mb-2">
                Dewan Juri FAS 2026
            </span>
            <h2 class="text-lg font-extrabold leading-snug">{{ $user->name }}</h2>
            <p class="text-xs text-emerald-300 mt-0.5">{{ $user->phone }}</p>

            <div class="mt-4 flex items-center space-x-2">
                <a href="{{ route('judge.scanner') }}" 
                   class="flex-1 py-2.5 px-4 rounded-xl bg-white text-emerald-900 font-extrabold text-xs text-center shadow-sm hover:bg-slate-100 flex items-center justify-center space-x-1.5 active:scale-98 transition-transform">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Pindai QR Peserta</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Offline Drafts Indicator if any -->
    <div x-show="draftsCount > 0" class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center justify-between shadow-xs">
        <div class="flex items-center space-x-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
            <div class="text-xs">
                <span class="font-bold" x-text="draftsCount + ' Draft Nilai Offline'"></span>
                <span class="block text-[11px] text-amber-700">Tersimpan di perangkat Anda.</span>
            </div>
        </div>
        <button @click="triggerSync()" :disabled="!isOnline || isSyncing"
                class="px-3 py-1 rounded-lg bg-amber-500 text-emerald-950 font-bold text-xs hover:bg-amber-400 disabled:opacity-50">
            Sinkronkan
        </button>
    </div>

    <!-- Assigned Competitions List -->
    <div>
        <div class="flex items-center justify-between mb-2.5 px-1">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Cabang Lomba Ditugaskan ({{ $assignments->count() }})</h3>
        </div>

        <div class="space-y-3">
            @forelse($assignments as $assignment)
            @php
                $comp = $assignment->competition;
                $entriesCount = $comp->entries->count();
                $scoredCount = $comp->entries->filter(function($e) {
                    return $e->scoreSubmissions->isNotEmpty();
                })->count();
            @endphp
            <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs hover:border-emerald-300 transition-colors">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $comp->format === 'team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }} mb-1">
                            {{ $comp->format === 'team' ? 'Beregu' : 'Individu' }}
                        </span>
                        <h4 class="text-sm font-bold text-slate-900">{{ $comp->name }}</h4>
                        <p class="text-[11px] text-slate-500">Tingkat: {{ $comp->eligibility_rule ?? 'Semua' }} • Lokasi: {{ $comp->location ?? '-' }}</p>
                    </div>

                    <!-- Permission Badges -->
                    <div class="flex flex-col items-end space-y-1">
                        @if($assignment->can_score)
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">
                                Berhak Menilai
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600">
                                Hanya Lihat
                            </span>
                        @endif

                        @if($assignment->can_view_peer_scores)
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-medium bg-indigo-50 text-indigo-700">
                                Nilai Juri Lain: Aktif
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between text-[11px] text-slate-600 mb-1">
                        <span>Peserta Dinilai:</span>
                        <span class="font-bold text-emerald-800">{{ $scoredCount }} / {{ $entriesCount }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-600 h-1.5 rounded-full transition-all duration-500" 
                             style="width: {{ $entriesCount > 0 ? ($scoredCount / $entriesCount) * 100 : 0 }}%"></div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="mt-3 flex space-x-2">
                    <a href="{{ route('judge.competition.entries', $comp->id) }}" 
                       class="flex-1 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs text-center border border-emerald-200 transition-colors">
                        Buka Daftar Peserta Lomba →
                    </a>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-200">
                <p class="text-xs">Anda belum ditugaskan pada cabang lomba manapun.</p>
                <p class="text-[11px] mt-1 text-slate-400">Silakan hubungi Master Admin untuk mendapatkan tugas penjurian.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
