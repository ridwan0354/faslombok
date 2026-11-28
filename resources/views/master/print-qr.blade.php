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
    min-height: 290mm;
    margin: 0 auto 1.25rem auto;
    padding: 6mm 5mm;
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
    gap: 4mm 4mm;
}

.badge-card {
    width: var(--card-w);
    height: var(--card-h);
    box-sizing: border-box;
    background: #ffffff;
    border: 1px dashed #64748b;
    border-radius: 2mm;
    overflow: hidden;
    position: relative;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

.badge-bg-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: fill;
    z-index: 0;
    pointer-events: none;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

.badge-field {
    position: absolute;
    z-index: 2;
    display: flex;
    align-items: center;
    color: #3b0764;
    font-weight: 800;
    line-height: 1.08;
    text-align: left;
    overflow: hidden;
}

/* Pill 1: Nama : (white band y=36.7%..48.1%, colon ends x=33.7%, pill ends x=87.4%) */
.badge-field-name {
    top: 36.7%;
    height: 11.4%;
    left: 35.4%;
    width: 50.4%;
    font-size: 8.4pt;
}

.badge-field-name > span {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
}

/* Pill 2: Kelas : (left of QR box) */
.badge-field-grade {
    top: 49.9%;
    height: 11.2%;
    left: 35.4%;
    width: 29.2%;
    font-size: 8.2pt;
    white-space: nowrap;
}

/* Pill 3: TPQ : (left of QR box) */
.badge-field-tpq {
    top: 63.0%;
    height: 11.3%;
    left: 35.4%;
    width: 29.2%;
    font-size: 8.4pt;
    color: #6b21a8;
    white-space: nowrap;
}

.badge-field-grade > span,
.badge-field-tpq > span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: 100%;
}

/* QR Code floating card aligned neatly on right side beside Kelas & TPQ without covering kids or Lombok */
.badge-qr-wrap {
    position: absolute;
    z-index: 3;
    top: 49.8%;
    left: 65.5%;
    width: calc(var(--card-h) * 0.285);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.badge-qr-box {
    width: calc(var(--card-h) * 0.285);
    height: calc(var(--card-h) * 0.285);
    padding: 0.7mm;
    background: #ffffff;
    border: 0.35mm solid #6b21a8;
    border-radius: 1.5mm;
    box-shadow: 0 1px 3px rgba(59, 7, 100, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

.badge-qr-box svg {
    width: 100% !important;
    height: 100% !important;
    display: block;
}

.badge-code-pill {
    margin-top: 0.7mm;
    padding: 0.25mm 1.3mm;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.96);
    border: 0.25mm solid #a855f7;
    color: #3b0764;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 5.2pt;
    font-weight: 800;
    line-height: 1.15;
    white-space: nowrap;
}

@page {
    size: A4 portrait;
    margin: 5mm;
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
                        Cetak Name Tag &amp; Badge QR Peserta ({{ count($badges) }} Kartu)
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-200">
                        {{ $sheetCount }} Lembar A4 • {{ str_replace('.', ',', (string) ($activeSize['w'] / 10)) }} × {{ str_replace('.', ',', (string) ($activeSize['h'] / 10)) }} cm
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
                        <img src="/images/nametag-peserta-bg.jpg" alt="" class="badge-bg-img">

                        <!-- Field 1: Nama -->
                        <div class="badge-field badge-field-name">
                            <span>{{ $b['name'] }}</span>
                        </div>

                        <!-- Field 2: Kelas -->
                        <div class="badge-field badge-field-grade">
                            <span>{{ $b['grade'] }}</span>
                        </div>

                        <!-- Field 3: TPQ / Kelompok -->
                        <div class="badge-field badge-field-tpq">
                            <span>{{ $b['group'] }}</span>
                        </div>

                        <!-- Field 4: QR Code + Kode -->
                        <div class="badge-qr-wrap">
                            <div class="badge-qr-box">
                                {!! $b['qr_svg'] !!}
                            </div>
                            <span class="badge-code-pill">{{ $b['code'] }}</span>
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
