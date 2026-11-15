@extends('layouts.app')

@section('title', 'Cetak Kartu & Badge QR Peserta - FAS Lombok 2026')

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background-color: white !important;
        color: black !important;
    }
    .badge-card {
        page-break-inside: avoid;
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px dashed #94a3b8 !important;
    }
}
</style>
@endpush

@section('body')
<div class="min-h-screen bg-slate-100 py-6 px-4">
    <!-- Top Action Bar (hidden on print) -->
    <div class="max-w-6xl mx-auto mb-6 bg-white rounded-2xl p-4 shadow-sm flex items-center justify-between no-print border border-slate-200">
        <div>
            <h1 class="text-base font-extrabold text-slate-900">Cetak Kartu & Badge QR Peserta ({{ count($badges) }} Kartu)</h1>
            <p class="text-xs text-slate-500">Klik tombol Cetak / PDF untuk mencetak kartu tanda peserta resmi.</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('master.participants') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">
                ← Kembali
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-xs flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    <!-- Printable Cards Grid -->
    <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        @foreach($badges as $b)
        <div class="badge-card bg-white rounded-3xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between text-center relative overflow-hidden">
            <!-- Card Header Ribbon -->
            <div class="bg-emerald-900 text-white -mx-5 -mt-5 p-3.5 mb-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-amber-300">Festival Anak Sholeh 2026</h3>
                <p class="text-[10px] text-emerald-200 font-medium">Lombok • Kartu Tanda Peserta Resmi</p>
            </div>

            <!-- QR Code Container -->
            <div class="flex justify-center my-2">
                <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-200 inline-block shadow-2xs">
                    {!! $b['qr_svg'] !!}
                </div>
            </div>

            <div class="space-y-1 mt-2">
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-100 text-slate-800">
                    {{ $b['code'] }}
                </span>
                <h2 class="text-sm sm:text-base font-extrabold text-slate-900 leading-tight">{{ $b['name'] }}</h2>
                <p class="text-xs font-semibold text-emerald-800">{{ $b['group'] }}</p>
                <p class="text-[11px] text-slate-500 font-medium">Tingkat: {{ $b['grade'] }}</p>
            </div>

            <div class="mt-3 pt-3 border-t border-slate-100 text-[10px] text-slate-400">
                <span class="font-bold text-slate-600">Lomba:</span> {{ $b['comps'] ?: 'Semua' }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
