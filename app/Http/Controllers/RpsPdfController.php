<?php

namespace App\Http\Controllers;

use App\Models\Rps;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RpsPdfController extends Controller
{
    /**
     * Generate RPS PDF
     */
    public function generate(Rps $rps)
    {
        // Load all relationships needed for the PDF
        $rps->load([
            'mataKuliah.prodi',
            'mataKuliah.cpmks.cpl',
            'mataKuliah.cpmks.subCpmks',
            'details.subCpmk',
            'semester.tahunAkademik',
            'dosen',
        ]);

        // Get CPMK & Sub-CPMK for this MK
        $cpmks = \App\Models\Cpmk::where('mata_kuliah_id', $rps->mata_kuliah_id)
            ->with(['cpl', 'subCpmks'])
            ->orderBy('kode')
            ->get();

        // Group CPL by kategori (Sikap, Pengetahuan, Keterampilan Umum, Keterampilan Khusus)
        $cplKategori = [];
        foreach ($cpmks as $cpmk) {
            if ($cpmk->cpl) {
                $kategori = $cpmk->cpl->kategori ?? 'Lainnya';
                if (!isset($cplKategori[$kategori])) {
                    $cplKategori[$kategori] = [];
                }
                $cplKategori[$kategori][] = $cpmk->cpl->deskripsi;
            }
        }

        // Flatten SubCPMKs
        $subCpmks = $cpmks->flatMap(fn($c) => $c->subCpmks);

        // Prepare data for PDF
        $data = [
            'rps' => $rps,
            'mk' => $rps->mataKuliah,
            'prodi' => $rps->mataKuliah->prodi,
            'semester' => $rps->semester,
            'cpmks' => $cpmks,
            'subCpmks' => $subCpmks,
            'cplKategori' => $cplKategori,
            'cplKategori' => $cplKategori,
            'details' => $rps->details->sortBy('pertemuan')->values(),
            'tanggal' => now()->translatedFormat('d F Y'),
            // Placeholder for signatures - can be configured later
            'pengembang' => $rps->dosen?->name ?? 'Dosen Pengampu',
            'koordinator' => $rps->mataKuliah->prodi->koordinator ?? '-',
            'kaprodi' => $rps->mataKuliah->prodi->kaprodi ?? 'Ketua Program Studi',
        ];

        // Add rowspans calculation
        $data['rowspans'] = $this->calculateRowspans($data['details']);

        $pdf = Pdf::loadView('pdf.rps', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = 'RPS_' . str_replace(' ', '_', $rps->mataKuliah->kode) . '_' . date('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Preview RPS PDF in browser
     */
    public function preview(Rps $rps)
    {
        // Same loading as generate
        $rps->load([
            'mataKuliah.prodi',
            'mataKuliah.cpmks.cpl',
            'mataKuliah.cpmks.subCpmks',
            'details.subCpmk',
            'semester.tahunAkademik',
            'dosen',
        ]);

        $cpmks = \App\Models\Cpmk::where('mata_kuliah_id', $rps->mata_kuliah_id)
            ->with(['cpl', 'subCpmks'])
            ->orderBy('kode')
            ->get();

        $cplKategori = [];
        foreach ($cpmks as $cpmk) {
            if ($cpmk->cpl) {
                $kategori = $cpmk->cpl->kategori ?? 'Lainnya';
                if (!isset($cplKategori[$kategori])) {
                    $cplKategori[$kategori] = [];
                }
                $cplKategori[$kategori][] = $cpmk->cpl->deskripsi;
            }
        }

        $subCpmks = $cpmks->flatMap(fn($c) => $c->subCpmks);

        $data = [
            'rps' => $rps,
            'mk' => $rps->mataKuliah,
            'prodi' => $rps->mataKuliah->prodi,
            'semester' => $rps->semester,
            'cpmks' => $cpmks,
            'subCpmks' => $subCpmks,
            'cplKategori' => $cplKategori,
            'details' => $rps->details->sortBy('pertemuan')->values(),
            'tanggal' => now()->translatedFormat('d F Y'),
            'pengembang' => $rps->dosen?->name ?? 'Dosen Pengampu',
            'koordinator' => $rps->mataKuliah->prodi->koordinator ?? '-',
            'kaprodi' => $rps->mataKuliah->prodi->kaprodi ?? 'Ketua Program Studi',
        ];

        // Add rowspans calculation
        $data['rowspans'] = $this->calculateRowspans($data['details']);

        if (request()->has('html')) {
            return view('pdf.rps', $data);
        }

        $pdf = Pdf::loadView('pdf.rps', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        return $pdf->stream('RPS_Preview.pdf');
    }

    /**
     * Calculate rowspans for Sub-CPMK column
     */
    private function calculateRowspans($details)
    {
        $rowspans = [];
        $prevSubCpmkId = null;
        $counter = 0;
        $startIdx = -1;

        // Convert to array values to ensure 0-indexed keys
        $values = $details->values();

        foreach ($values as $index => $detail) {
            $currentId = $detail->sub_cpmk_id;

            // If it's the start or different from previous
            if ($index === 0 || $currentId !== $prevSubCpmkId) {
                // Save previous count
                if ($startIdx !== -1) {
                    $rowspans[$startIdx] = $counter;
                }

                // Reset
                $startIdx = $index;
                $counter = 1;
                $prevSubCpmkId = $currentId;
            } else {
                // Same ID, increment
                $counter++;
                // Set current row to 0 (hidden)
                $rowspans[$index] = 0;
            }
        }

        // Save last batch
        if ($startIdx !== -1) {
            $rowspans[$startIdx] = $counter;
        }

        return $rowspans;
    }
}
