<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Piagam Penghargaan FAS Lombok 2026</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Bar (Hidden when printing) */
        .top-action-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            background: #f1f5f9;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #e2e8f0;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            color: #ffffff;
            background: linear-gradient(to right, #581c87, #be185d);
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .btn-print:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        /* Certificate Page Setup */
        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media screen {
            .page-container {
                padding: 24px 0;
            }
            .certificate-page {
                width: 297mm;
                height: 210mm;
                margin: 20px auto;
                background-color: #ffffff;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                position: relative;
                overflow: hidden;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: transparent !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .page-container {
                padding: 0 !important;
                margin: 0 !important;
            }
            .certificate-page {
                width: 297mm !important;
                height: 210mm !important;
                page-break-after: always !important;
                break-after: page !important;
                position: relative !important;
                overflow: hidden !important;
                box-shadow: none !important;
                margin: 0 !important;
                border: none !important;
            }
        }

        .cert-bg-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
        }

        .cert-field {
            position: absolute;
            z-index: 10;
            white-space: nowrap;
            line-height: 1.2;
        }
    </style>
</head>
<body>

    <!-- Action Bar -->
    <div class="top-action-bar no-print">
        <div>
            <h2 style="font-size: 15px; font-weight: 800; color: #3b0764;">
                Cetak Piagam / Sertifikat ({{ count($recipients) }} Lembar A4)
            </h2>
            <p style="font-size: 11px; color: #64748b; margin-top: 2px;">
                Format A4 Landscape (297 × 210 mm). Pada jendela print, pastikan pilih orientasi <b>Landscape</b> dan centang <b>Background graphics</b>.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('master.certificates') }}" class="btn-back">
                ← Kembali ke Pengaturan
            </a>
            <button onclick="window.print()" class="btn-print">
                🖨️ Cetak / Simpan PDF (Cmd+P)
            </button>
        </div>
    </div>

    <!-- Certificate Pages Container -->
    <div class="page-container">
        @forelse($recipients as $item)
        <div class="certificate-page">
            <!-- Background Image -->
            <img src="{{ $template['background_image'] }}" alt="Template Piagam" class="cert-bg-img">

            <!-- Field: Nomor Sertifikat -->
            @if(!empty($template['fields']['number']['enabled']))
            <div class="cert-field" style="
                left: {{ $template['fields']['number']['x'] }}%;
                top: {{ $template['fields']['number']['y'] }}%;
                transform: translate(-50%, -50%);
                font-size: {{ $template['fields']['number']['font_size'] }}px;
                font-family: {{ $template['fields']['number']['font_family'] }};
                font-weight: {{ $template['fields']['number']['font_weight'] }};
                color: {{ $template['fields']['number']['color'] }};
                text-align: {{ $template['fields']['number']['align'] }};
            ">
                {{ $template['fields']['number']['prefix'] ?? '' }}{{ $item['certificate_number'] }}
            </div>
            @endif

            <!-- Field: Nama Penerima -->
            @if(!empty($template['fields']['name']['enabled']))
            <div class="cert-field" style="
                left: {{ $template['fields']['name']['x'] }}%;
                top: {{ $template['fields']['name']['y'] }}%;
                transform: translate(-50%, -50%);
                font-size: {{ $template['fields']['name']['font_size'] }}px;
                font-family: {{ $template['fields']['name']['font_family'] }};
                font-weight: {{ $template['fields']['name']['font_weight'] }};
                color: {{ $template['fields']['name']['color'] }};
                text-align: {{ $template['fields']['name']['align'] }};
            ">
                {{ $template['fields']['name']['prefix'] ?? '' }}{{ $item['name'] }}
            </div>
            @endif

            <!-- Field: Predikat / Juara -->
            @if(!empty($template['fields']['award']['enabled']))
            <div class="cert-field" style="
                left: {{ $template['fields']['award']['x'] }}%;
                top: {{ $template['fields']['award']['y'] }}%;
                transform: translate(-50%, -50%);
                font-size: {{ $template['fields']['award']['font_size'] }}px;
                font-family: {{ $template['fields']['award']['font_family'] }};
                font-weight: {{ $template['fields']['award']['font_weight'] }};
                color: {{ $template['fields']['award']['color'] }};
                text-align: {{ $template['fields']['award']['align'] }};
            ">
                {{ $template['fields']['award']['prefix'] ?? '' }}{{ $item['award'] }}
            </div>
            @endif

            <!-- Field: Cabang Lomba -->
            @if(!empty($template['fields']['competition']['enabled']))
            <div class="cert-field" style="
                left: {{ $template['fields']['competition']['x'] }}%;
                top: {{ $template['fields']['competition']['y'] }}%;
                transform: translate(-50%, -50%);
                font-size: {{ $template['fields']['competition']['font_size'] }}px;
                font-family: {{ $template['fields']['competition']['font_family'] }};
                font-weight: {{ $template['fields']['competition']['font_weight'] }};
                color: {{ $template['fields']['competition']['color'] }};
                text-align: {{ $template['fields']['competition']['align'] }};
            ">
                {{ $template['fields']['competition']['prefix'] ?? '' }}{{ $item['competition'] }}
            </div>
            @endif

            <!-- Field: Kelompok / TPQ -->
            @if(!empty($template['fields']['group']['enabled']))
            <div class="cert-field" style="
                left: {{ $template['fields']['group']['x'] }}%;
                top: {{ $template['fields']['group']['y'] }}%;
                transform: translate(-50%, -50%);
                font-size: {{ $template['fields']['group']['font_size'] }}px;
                font-family: {{ $template['fields']['group']['font_family'] }};
                font-weight: {{ $template['fields']['group']['font_weight'] }};
                color: {{ $template['fields']['group']['color'] }};
                text-align: {{ $template['fields']['group']['align'] }};
            ">
                {{ $template['fields']['group']['prefix'] ?? '' }}{{ $item['group'] }}
            </div>
            @endif

        </div>
        @empty
        <div style="text-align: center; padding: 100px 20px; color: #64748b;">
            <h3>Tidak ada piagam yang dapat dicetak dengan filter saat ini.</h3>
            <p style="margin-top: 8px;">Silakan pilih cabang lomba lain atau reset filter pencarian Anda.</p>
        </div>
        @endforelse
    </div>

</body>
</html>
