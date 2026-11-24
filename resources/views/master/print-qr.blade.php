@extends('layouts.app')

@section('title', 'Cetak Kartu & Badge QR Peserta - FAS Lombok 2026')

@php
    $activeSize = $sizePresets[$selectedSize];
    $pages = $badges->chunk(8);
    $sheetCount = $pages->count();
@endphp

@push('styles')
<style>
:root {
    --card-w: {{ $activeSize['w'] }}mm;
    --card-h: {{ $activeSize['h'] }}mm;
}

.sheet-wrapper {
    width: 100%;
    overflow-x: auto;
    padding-bottom: 1.5rem;
}

.sheet-page {
    width: 210mm;
    min-height: 270mm;
    margin: 0 auto 1.25rem auto;
    padding: 8mm 6mm;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
}

.badge-grid {
    display: grid;
    grid-template-columns: repeat(2, var(--card-w));
    grid-auto-rows: var(--card-h);
    justify-content: center;
    gap: 5mm 4mm;
}

.badge-card {
    width: var(--card-w);
    height: var(--card-h);
    box-sizing: border-box;
    background: #ffffff;
    border: 1px dashed #64748b;
    border-radius: 2mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

.badge-header {
    height: 8.5mm;
    background: linear-gradient(90deg, #3b0764 0%, #581c87 60%, #831843 100%);
    color: #ffffff;
    padding: 0 3mm;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

.badge-body {
    flex: 1;
    min-height: 0;
    padding: 2.2mm 3mm;
    display: flex;
    align-items: center;
    gap: 2.8mm;
}

.badge-qr-col {
    width: 25.5mm;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.badge-qr-box {
    width: 24.5mm;
    height: 24.5mm;
    padding: 1mm;
    border: 0.3mm solid #cbd5e1;
    border-radius: 1.5mm;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.badge-qr-box svg {
    width: 100% !important;
    height: 100% !important;
    display: block;
}

.badge-code {
    margin-top: 1.3mm;
    padding: 0.4mm 1.8mm;
    border-radius: 999px;
    background: #f3e8ff;
    border: 0.25mm solid #d8b4fe;
    color: #3b0764;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 6.2pt;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.badge-info-col {
    flex: 1;
    min-width: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-align: left;
}

.badge-name {
    font-size: 9.5pt;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.15;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
}

.badge-group {
    margin-top: 1.4mm;
    display: inline-block;
    padding: 0.45mm 2mm;
    border-radius: 999px;
    background: #fdf2f8;
    border: 0.25mm solid #f9a8d4;
    color: #9d174d;
    font-size: 7.2pt;
    font-weight: 800;
    line-height: 1.2;
}

.badge-grade {
    margin-top: 1mm;
    font-size: 6.8pt;
    color: #475569;
    font-weight: 600;
    line-height: 1.2;
}

.badge-comps {
    border-top: 0.25mm solid #e2e8f0;
    padding-top: 1.3mm;
    font-size: 6.3pt;
    line-height: 1.25;
    color: #475569;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@page {
    size: A4 portrait;
    margin: 6mm;
}

@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .no-print {
        display: none !important;
    }
    html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .print-outer {
        background: #ffffff !important;
        padding: 0 !important;
        min-height: auto !important;
    }
    .sheet-wrapper {
        overflow: visible !important;
        padding: 0 !important;
    }
    .sheet-page {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 2mm 0 0 0 !important;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        page-break-after: always;
        break-after: page;
    }
    .sheet-page:last-child {
        page-break-after: auto;
        break-after: auto;
    }
    .badge-card {
        page-break-inside: avoid;
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px dashed #64748b !important;
    }
}
</style>
@endpush

@section('body')
<div class="print-outer min-h-screen bg-slate-100 py-6 px-4">
    <!-- Top Filter & Action Bar (hidden on print) -->
    <div class="max-w-6xl mx-auto mb-5 bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-200 no-print space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-base sm:text-lg font-extrabold text-purple-950">
                        Cetak Name Tag & Badge QR Peserta ({{ count($badges) }} Kartu)
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-200">
                        {{ $sheetCount }} Lembar A4 • {{ $activeSize['w'] / 10 }} × {{ str_replace('.', ',', (string) ($activeSize['h'] / 10)) }} cm
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    @if($selectedGroup)
                        Menampilkan peserta kelompok <span class="font-bold text-pink-700">{{ $selectedGroup->name }}</span>.
                    @else
                        Menampilkan seluruh kelompok ({{ $groups->count() }} Kelompok).
                    @endif
                    Garis putus-putus sudah pas ukuran plastik name tag peniti — tinggal potong lalu selipkan.
                </p>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('master.participants') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200">
                    ← Kembali ke Peserta
                </a>
                <button onclick="window.print()" class="px-4 py-2 bg-gradient-to-r from-purple-800 to-pink-700 hover:from-purple-900 hover:to-pink-800 text-white font-bold text-xs rounded-xl shadow-xs flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak / Simpan PDF</span>
                </button>
            </div>
        </div>

        <!-- Filter Controls -->
        <form method="GET" action="{{ route('master.print.qr') }}" class="pt-3 border-t border-slate-100 flex flex-wrap items-end gap-3">
            @if($selectedParticipantId)
                <input type="hidden" name="participant_id" value="{{ $selectedParticipantId }}">
            @endif

            <div class="w-full sm:w-64">
                <label for="group_id" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide mb-1">
                    Filter Kelompok / TPQ
                </label>
                <select id="group_id" name="group_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs font-semibold border border-slate-300 rounded-xl bg-white text-slate-800 focus:ring-2 focus:ring-purple-600 focus:border-purple-600">
                    <option value="">Semua Kelompok ({{ $groups->sum('participants_count') }} Peserta)</option>
                    @foreach($groups as $grp)
                        <option value="{{ $grp->id }}" {{ (int) $selectedGroupId === (int) $grp->id ? 'selected' : '' }}>
                            {{ $grp->name }} ({{ $grp->participants_count }} Peserta)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-72">
                <label for="size" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide mb-1">
                    Ukuran Name Tag Peniti
                </label>
                <select id="size" name="size" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs font-semibold border border-slate-300 rounded-xl bg-white text-slate-800 focus:ring-2 focus:ring-purple-600 focus:border-purple-600">
                    @foreach($sizePresets as $presetKey => $preset)
                        <option value="{{ $presetKey }}" {{ $selectedSize === $presetKey ? 'selected' : '' }}>
                            {{ $preset['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($selectedGroupId || $selectedParticipantId)
                <a href="{{ route('master.print.qr', ['size' => $selectedSize]) }}"
                   class="px-3.5 py-2 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl">
                    Reset Filter
                </a>
            @endif

            <div class="ml-auto text-[11px] text-slate-500 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                <span class="font-bold text-amber-900">Tips Cetak:</span> Kertas <span class="font-bold">A4</span> • Skala <span class="font-bold">100% (Actual Size)</span> • Centang <span class="font-bold">Background graphics</span>
            </div>
        </form>
    </div>

    <!-- Printable A4 Sheets (8 Name Tags per A4 Sheet) -->
    @if($badges->isEmpty())
        <div class="max-w-xl mx-auto bg-white rounded-2xl p-10 text-center border border-slate-200 shadow-xs no-print">
            <p class="text-sm font-bold text-slate-700">Tidak ada peserta aktif pada kelompok yang dipilih.</p>
            <a href="{{ route('master.print.qr') }}" class="inline-block mt-3 px-4 py-2 text-xs font-bold bg-purple-700 text-white rounded-xl">
                Tampilkan Semua Kelompok
            </a>
        </div>
    @else
        <div class="sheet-wrapper">
            @foreach($pages as $pageIndex => $pageBadges)
            <div class="sheet-page">
                <div class="text-[10px] font-bold text-slate-400 mb-2 flex items-center justify-between no-print">
                    <span>Lembar A4 #{{ $pageIndex + 1 }} ({{ count($pageBadges) }} Name Tag)</span>
                    <span>Ukuran Potong: {{ $activeSize['w'] }} mm × {{ $activeSize['h'] }} mm</span>
                </div>

                <div class="badge-grid">
                    @foreach($pageBadges as $b)
                    <div class="badge-card">
                        <!-- Top Header Ribbon -->
                        <div class="badge-header">
                            <div style="display:flex;align-items:center;gap:1.6mm;">
                                <img src="/images/logo.png" alt="PPG Lombok" style="width:5.2mm;height:5.2mm;object-fit:contain;">
                                <span style="font-size:6.8pt;font-weight:900;letter-spacing:0.03em;color:#fcd34d;text-transform:uppercase;">FAS &amp; Bazar Remaja 2026</span>
                            </div>
                            <span style="font-size:5.8pt;font-weight:700;color:#f5d0fe;text-transform:uppercase;letter-spacing:0.04em;">Lombok</span>
                        </div>

                        <!-- Horizontal Body: QR (Left) + Identity (Right) -->
                        <div class="badge-body">
                            <div class="badge-qr-col">
                                <div class="badge-qr-box">
                                    {!! $b['qr_svg'] !!}
                                </div>
                                <span class="badge-code">{{ $b['code'] }}</span>
                            </div>

                            <div class="badge-info-col">
                                <div>
                                    <div class="badge-name">{{ $b['name'] }}</div>
                                    <div>
                                        <span class="badge-group">{{ $b['group'] }}</span>
                                    </div>
                                    <div class="badge-grade">Tingkat: {{ $b['grade'] }}</div>
                                </div>

                                <div class="badge-comps">
                                    <span style="font-weight:700;color:#1e293b;">Lomba:</span> {{ $b['comps'] ?: 'Semua' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
