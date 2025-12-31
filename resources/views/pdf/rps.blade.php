<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>RPS - {{ $mk->nama ?? 'Mata Kuliah' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
            padding: 20px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Main Table Style */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 20px;
        }

        table.main-table th,
        table.main-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
        }

        /* Headers inside table */
        .bg-header {
            background-color: #e6e6e6;
            font-weight: bold;
            text-align: center;
        }

        .bg-sub-header {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        /* Specific widths and alignments */
        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .align-top {
            vertical-align: top !important;
        }

        .align-bottom {
            vertical-align: bottom !important;
        }

        /* Header Section specifics */
        .header-logo img {
            width: 70px;
            height: auto;
        }

        .header-title {
            text-align: center;
        }

        .header-title h1 {
            font-size: 14pt;
            margin: 0;
        }

        .header-title h2 {
            font-size: 11pt;
            margin: 0;
            font-weight: normal;
        }

        .header-doc-code {
            font-size: 8pt;
            text-align: right;
            vertical-align: top;
        }

        /* Matrix Table for Weekly Plan */
        .matrix-table th {
            text-align: center;
            vertical-align: middle;
        }

        ul,
        ol {
            margin-left: 15px;
            margin-top: 0;
            margin-bottom: 0;
        }

        li {
            margin-bottom: 2px;
        }
    </style>
</head>

<body>

    <table class="main-table" style="border-bottom: none; margin-bottom: 0;">
        {{-- HEADER SECTION --}}
        <tr>
            <td style="width: 100px; text-align: center; border-right: none;">
                {{-- Placeholder Logo or Real Logo --}}
                <div
                    style="width: 70px; height: 70px; margin: 0 auto; display: flex; align-items: center; justify-content: center; ">
                    <span style="font-size: 10px; color: #999;">LOGO</span>
                </div>
            </td>
            <td colspan="5" style="text-align: center; border-left: none; border-right: none;">
                <h1 style="font-size: 14pt; font-weight: bold;">UNIVERSITAS IBN KHALDUN BOGOR</h1>
                <h2 style="font-size: 12pt; font-weight: bold;">SEKOLAH PASCASARJANA</h2>
                <h2 style="font-size: 12pt; font-weight: bold;">PROGRAM STUDI {{ strtoupper($prodi->nama ?? '') }}</h2>
            </td>
            <td style="width: 100px; vertical-align: top; border-left: none; font-size: 8pt;">
                <strong>Kode Dokumen</strong><br>
                {{-- Placeholder/Random Code --}}
            </td>
        </tr>

        {{-- TITLE --}}
        <tr>
            <td colspan="7" class="bg-header" style="padding: 10px; font-size: 12pt;">
                RENCANA PEMBELAJARAN SEMESTER
            </td>
        </tr>

        {{-- IDENTITY ROW HEADERS (6 Columns now) --}}
        {{-- Total cols: 7 to match "Header Section" above? No, tables define their own cols if not strict.
        But wait, we want aligned borders.
        Let's try to stick to a grid.
        Header section used 1 + 5 + 1 = 7 cols? No, colspan="5".
        Let's use a 6-column grid for Identity.
        --}}
        <tr class="bg-header">
            <td colspan="2" style="width: 30%;">MATA KULIAH (MK)</td>
            <td style="width: 10%;">KODE</td>
            <td style="width: 20%;">Rumpun MK</td>
            <td style="width: 10%;">BOBOT (sks)</td>
            <td style="width: 10%;">SEMESTER</td>
            <td style="width: 20%;">Tgl Penyusunan</td>
        </tr>

        {{-- IDENTITY ROW VALUES --}}
        <tr>
            <td colspan="2" class="text-center text-bold">{{ $mk->nama ?? '-' }}</td>
            <td class="text-center">{{ $mk->kode ?? '-' }}</td>
            <td class="text-center">{{ $mk->rumpun ?? 'Matakuliah Wajib' }}</td>
            <td class="text-center">
                T={{ $mk->sks_teori ?? 0 }} &nbsp; P={{ $mk->sks_praktik ?? 0 }}
            </td>
            <td class="text-center">{{ $semester->nama ?? '-' }}</td>
            <td class="text-center">{{ $tanggal }}</td>
        </tr>

        {{-- OTORISASI --}}
        <tr>
            <td rowspan="2" class="text-bold align-top">OTORISASI</td>
            <td colspan="2" class="bg-sub-header align-top">Pengembang RPS</td>
            <td colspan="2" class="bg-sub-header align-top">Koordinator RMK</td>
            <td colspan="2" class="bg-sub-header align-top">Ketua Program Studi</td>
        </tr>
        <tr>
            <td colspan="2" style="height: 60px; vertical-align: bottom; text-align: center;">
                <br><br>
                <u>{!! $pengembang !!}</u>
            </td>
            <td colspan="2" style="vertical-align: bottom; text-align: center;">
                <br><br>
                <u>{{ $koordinator }}</u>
            </td>
            <td colspan="2" style="vertical-align: bottom; text-align: center;">
                <br>
                @if(isset($qrCode))
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" width="60" height="60"><br>
                    <div style="font-size: 7pt; font-style: italic; margin-top: 2px;">
                        Dokumen ini telah disahkan secara elektronik
                    </div>
                    <span style="font-size: 7pt; color: #555;">(Verifikasi: {{ $verificationCode }})</span><br>
                @else
                    <br><br>
                @endif
                <u>{{ $kaprodi }}</u>
            </td>
        </tr>

        {{-- CAPAIAN PEMBELAJARAN (CP) --}}
        <tr>
            <td rowspan="2" class="align-top text-bold">Capaian Pembelajaran (CP)</td>
            <td colspan="6" class="bg-sub-header text-left">CPL-PRODI yang dibebankan pada MK</td>
        </tr>
        <tr>
            <td colspan="6" class="align-top">
                <ul style="list-style-type: none; margin: 0; padding: 0;">
                    @if(!empty($cplKategori) && count($cplKategori) > 0)
                        @foreach($cplKategori as $kat => $deskripsiList)
                            <li style="margin-bottom: 5px;">
                                <strong>{{ $kat }}:</strong> <br>
                                - {{ implode('; ', array_unique($deskripsiList)) }}
                            </li>
                        @endforeach
                    @else
                        <li>- Data CPL belum tersedia -</li>
                    @endif
                </ul>
            </td>
        </tr>
        <tr>
            <td class="align-top">&nbsp;</td>
            <td colspan="6" class="bg-sub-header text-left" style="border-top: none;">Capaian Pembelajaran Mata Kuliah
                (CPMK)</td>
        </tr>
        <tr>
            <td class="align-top text-bold">&nbsp;</td>
            <td colspan="6" class="align-top">
                @foreach($cpmks as $cpmk)
                    <div style="margin-bottom: 4px;"><strong>{{ $cpmk->kode }}:</strong> {{ $cpmk->deskripsi }}</div>
                @endforeach
                @if($cpmks->isEmpty()) - @endif
            </td>
        </tr>

        {{-- DESKRIPSI SINGKAT --}}
        <tr>
            <td colspan="7" class="bg-sub-header">Deskripsi Singkat MK</td>
        </tr>
        <tr>
            <td colspan="7" class="align-top" style="min-height: 50px;">
                {{ $rps->deskripsi ?? '-' }}
            </td>
        </tr>

        {{-- PUSTAKA --}}
        <tr>
            <td rowspan="2" class="align-top text-bold">Pustaka</td>
            <td colspan="1" class="bg-sub-header">Utama :</td>
            <td colspan="5" class="align-top">
                {!! nl2br(e($rps->pustaka_utama ?? '-')) !!}
            </td>
        </tr>
        <tr>
            <td colspan="1" class="bg-sub-header">Pendukung :</td>
            <td colspan="5" class="align-top">
                {!! nl2br(e($rps->pustaka_pendukung ?? '-')) !!}
            </td>
        </tr>

        {{-- DOSEN PENGAMPU --}}
        <tr>
            <td colspan="2" class="bg-sub-header">Dosen Pengampu</td>
            <td colspan="5">{!! $dosen_pengampu ?? $pengembang !!}</td>
        </tr>

    </table>

    {{-- WEEKLY PLAN TABLE (MATRIK) --}}
    {{-- Unified Look: Margin top 0, Border top removed to merge with previous table --}}
    <table class="main-table matrix-table" style="margin-top: -2px; border-top: none;">
        <thead>
            <tr class="bg-header">
                <td rowspan="2" style="width: 5%;">Mg Ke-</td>
                <td rowspan="2" style="width: 20%;">Kemampuan akhir tiap tahapan belajar<br>(Sub-CPMK)</td>
                <td colspan="2" style="width: 25%;">Penilaian</td>
                <td colspan="2" style="width: 20%;">Bentuk Pembelajaran,<br>Metode Pembelajaran,<br>Penugasan
                    Mahasiswa,<br>[ Estimasi Waktu]</td>
                <td rowspan="2" style="width: 20%;">Materi Pembelajaran<br>[ Pustaka ]</td>
                <td rowspan="2" style="width: 10%;">Bobot Penilaian<br>(%)</td>
            </tr>
            <tr class="bg-header">
                <td style="width: 15%;">Indikator</td>
                <td style="width: 10%;">Kriteria & Bentuk</td>
                <td style="width: 10%;">Luring (offline)</td>
                <td style="width: 10%;">Daring (online)</td>
            </tr>
            <tr class="bg-sub-header text-center" style="font-size: 8pt;">
                <td>(1)</td>
                <td>(2)</td>
                <td>(3)</td>
                <td>(4)</td>
                <td>(5)</td>
                <td>(6)</td>
                <td>(7)</td>
                <td>(8)</td>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $detail)
                <tr @if($detail->pertemuan == 8 || $detail->pertemuan == 16) style="background-color: #fff3e0;" @endif>
                    <td class="text-center">{{ $detail->pertemuan }}</td>

                    {{-- SUB-CPMK (Merged) --}}
                    @if(isset($rowspans[$index]) && $rowspans[$index] > 0)
                        <td rowspan="{{ $rowspans[$index] }}" class="align-top">
                            @if($detail->pertemuan == 8)
                                <strong>UJIAN TENGAH SEMESTER</strong>
                            @elseif($detail->pertemuan == 16)
                                <strong>UJIAN AKHIR SEMESTER</strong>
                            @else
                                <strong>{{ $detail->subCpmk->kode ?? '' }}</strong><br>
                                {{ $detail->subCpmk->deskripsi ?? '' }}
                            @endif
                        </td>
                    @elseif(!isset($rowspans[$index]))
                        {{-- Skip rendering cell if encompassed by rowspan, but we need to check if rowspan is 0, implying skip
                        --}}
                        {{-- The logic in controller sets rowspan=0 for skipped cells. So we only render if rowspan > 0 OR
                        rowspan is not set (fallback) --}}
                        {{-- Wait, if rowspan key exists and is 0, we simply DO NOT render the TD tag.
                        My logic above: elseif(!isset) renders.
                        What if isset and == 0? It falls through and renders nothing. Correct.
                        --}}
                        @if(!array_key_exists($index, $rowspans))
                            <td class="align-top">
                                <strong>{{ $detail->subCpmk->kode ?? '' }}</strong>
                            </td>
                        @endif
                    @endif

                    {{-- INDIKATOR --}}
                    <td class="align-top">{{ $detail->indikator ?? '-' }}</td>

                    {{-- KRITERIA & BENTUK --}}
                    <td class="align-top text-center">-</td>

                    {{-- LURING (Metode) --}}
                    <td class="align-top">{{ $detail->metode ?? '-' }}</td>

                    {{-- DARING --}}
                    <td class="align-top text-center">-</td>

                    {{-- MATERI --}}
                    <td class="align-top">{{ $detail->materi ?? '-' }}</td>

                    {{-- BOBOT --}}
                    <td class="text-center">{{ number_format($detail->bobot_nilai, 0) }}%</td>
                </tr>
            @endforeach
        </tbody>
        {{-- FOOTER REKAP --}}
        <tfoot>
            <tr>
                <td colspan="7" class="text-big text-right text-bold" style="text-align: right; padding-right: 10px;">
                    Total Bobot</td>
                <td class="text-center text-bold">{{ number_format($details->sum('bobot_nilai'), 0) }}%</td>
            </tr>
        </tfoot>
    </table>

    {{-- Note below table --}}
    <div style="font-size: 8pt; margin-bottom: 20px;">
        <strong>Catatan:</strong>
        <ol style="margin-left: 20px;">
            <li><strong>Capaian Pembelajaran Lulusan PRODI (CPL-PRODI)</strong> adalah kemampuan yang dimiliki oleh
                setiap lulusan PRODI yang merupakan internalisasi dari sikap, penguasaan pengetahuan dan ketrampilan
                sesuai dengan jenjang prodinya yang diperoleh melalui proses pembelajaran.</li>
            <li><strong>CPL yang dibebankan pada mata kuliah</strong> adalah beberapa capaian pembelajaran lulusan
                program studi (CPL-PRODI) yang digunakan untuk pembentukan/pengembangan sebuah mata kuliah yang terdiri
                dari aspek sikap, ketrampulan umum, ketrampilan khusus dan pengetahuan.</li>
            <li><strong>CP Mata kuliah (CPMK)</strong> adalah kemampuan yang dijabarkan secara spesifik dari CPL yang
                dibebankan pada mata kuliah, dan bersifat spesifik terhadap bahan kajian atau materi pembelajaran mata
                kuliah tersebut.</li>
            <li><strong>Sub-CPMK Mata kuliah (Sub-CPMK)</strong> adalah kemampuan yang dijabarkan secara spesifik dari
                CPMK yang dapat diukur atau diamati dan merupakan kemampuan akhir yang direncanakan pada tiap tahap
                pembelajaran, dan bersifat spesifik terhadap materi pembelajaran mata kuliah tersebut.</li>
            <li><strong>Indikator penilaian</strong> kemampuan dalam proses maupun hasil belajar mahasiswa adalah
                pernyataan spesifik dan terukur yang mengidentifikasi kemampuan atau kinerja hasil belajar mahasiswa
                yang disertai bukti-bukti.</li>
        </ol>
    </div>

    {{-- Document Info --}}
    <div class="footer">
        <p>Dokumen ini di-generate oleh SIAKAD Pascasarjana UIKA pada {{ now()->translatedFormat('d F Y H:i') }}</p>
    </div>
</body>

</html>