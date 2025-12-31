<?php

namespace App\Http\Controllers;

use App\Models\Rps;
use Inertia\Inertia;

class RpsVerificationController extends Controller
{
    /**
     * Verify RPS document by verification code (Public - no auth required)
     */
    public function verify(string $code)
    {
        $rps = Rps::where('verification_code', $code)
            ->with([
                'mataKuliah.prodi',
                'dosen',
                'approvedByGkm',
                'approvedByKaprodi',
                'semester',
            ])
            ->first();

        if (!$rps) {
            return Inertia::render('Rps/Verify', [
                'found' => false,
                'code' => $code,
                'message' => 'Dokumen dengan kode verifikasi tersebut tidak ditemukan.',
            ]);
        }

        return Inertia::render('Rps/Verify', [
            'found' => true,
            'code' => $code,
            'rps' => [
                'id' => $rps->id,
                'verification_code' => $rps->verification_code,
                'approval_status' => $rps->approval_status,
                'mata_kuliah' => $rps->mataKuliah?->nama,
                'kode_mk' => $rps->mataKuliah?->kode,
                'program_studi' => $rps->mataKuliah?->prodi?->nama,
                'semester' => $rps->semester?->nama,
                'dosen_pengembang' => $rps->dosen?->name,
                'approved_by_gkm' => $rps->approvedByGkm?->nama_gelar,
                'approved_by_gkm_at' => $rps->approved_by_gkm_at?->format('d F Y H:i'),
                'approved_by_kaprodi' => $rps->approvedByKaprodi?->nama_gelar,
                'approved_by_kaprodi_at' => $rps->approved_by_kaprodi_at?->format('d F Y H:i'),
                'is_approved' => $rps->isApproved(),
            ],
        ]);
    }
}
