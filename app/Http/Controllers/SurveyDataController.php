<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\KelasMatakuliah;
use App\Models\MataKuliah;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;

class SurveyDataController extends Controller
{
    /**
     * Get dynamic data options for survey questions
     * Supports: dosen, mahasiswa, prodi, kelas, mata_kuliah
     */
    public function getOptions(Request $request, string $type)
    {
        $activeTa = TahunAkademik::where('is_active', true)->first();
        $jadwalOnly = $request->boolean('jadwal_only', false);
        $prodiId = $request->input('prodi_id');
        $kelasId = $request->input('kelas_id');

        switch ($type) {
            case 'dosen':
                return $this->getDosen($jadwalOnly, $activeTa, $prodiId, $kelasId);

            case 'mahasiswa':
                return $this->getMahasiswa($prodiId);

            case 'prodi':
                return $this->getProdi();

            case 'kelas':
                $taId = $request->input('tahun_akademik_id');
                return $this->getKelas($activeTa, $prodiId, $taId);

            case 'mata_kuliah':
                return $this->getMataKuliah($prodiId);

            default:
                return response()->json(['error' => 'Invalid data type'], 400);
        }
    }

    /**
     * Get dosen list with optional jadwal filtering
     */
    protected function getDosen($jadwalOnly, $activeTa, $prodiId = null, $kelasId = null)
    {
        $query = Dosen::query()->select('id', 'nama', 'nidn', 'program_studi_id');

        // Filter by jadwal - only dosen assigned to active classes
        if ($jadwalOnly && $activeTa) {
            $query->whereHas('kelasMatakuliahs', function ($q) use ($activeTa, $kelasId) {
                $q->where('tahun_akademik_id', $activeTa->id);
                if ($kelasId) {
                    $q->where('kelas_matakuliah.id', $kelasId);
                }
            });
        }

        // Filter by prodi
        if ($prodiId) {
            $query->where('program_studi_id', $prodiId);
        }

        return response()->json([
            'data' => $query->orderBy('nama')->get()->map(fn($d) => [
                'id' => $d->id,
                'label' => $d->nama,
                'info' => $d->nidn,
            ]),
        ]);
    }

    /**
     * Get mahasiswa list
     */
    protected function getMahasiswa($prodiId = null)
    {
        $query = Mahasiswa::query()->select('id', 'nama', 'nim', 'program_studi_id');

        if ($prodiId) {
            $query->where('program_studi_id', $prodiId);
        }

        return response()->json([
            'data' => $query->orderBy('nama')->limit(500)->get()->map(fn($m) => [
                'id' => $m->id,
                'label' => $m->nama,
                'info' => $m->nim,
            ]),
        ]);
    }

    /**
     * Get prodi list
     */
    protected function getProdi()
    {
        return response()->json([
            'data' => ProgramStudi::orderBy('nama')->get()->map(fn($p) => [
                'id' => $p->id,
                'label' => $p->nama,
                'info' => $p->kode ?? '',
            ]),
        ]);
    }

    /**
     * Get kelas list for tahun akademik (via Kelas→Semester→TahunAkademik)
     */
    protected function getKelas($activeTa, $prodiId = null, $taId = null)
    {
        $tahunAkademikId = $taId ?? $activeTa?->id;

        $query = KelasMatakuliah::with(['mataKuliah', 'kelas.semester.tahunAkademik'])
            ->whereHas('kelas.semester', function ($q) use ($tahunAkademikId) {
                if ($tahunAkademikId) {
                    $q->where('tahun_akademik_id', $tahunAkademikId);
                }
            });

        if ($prodiId) {
            $query->whereHas('mataKuliah', fn($q) => $q->where('program_studi_id', $prodiId));
        }

        return response()->json([
            'data' => $query->get()->map(fn($k) => [
                'id' => $k->id,
                'nama' => ($k->kelas->nama ?? 'Kelas') . ' - ' . ($k->mataKuliah->nama ?? ''),
                'label' => ($k->kelas->nama ?? '') . ' - ' . ($k->mataKuliah->nama ?? ''),
                'info' => $k->mataKuliah->kode ?? '',
                'mata_kuliah' => $k->mataKuliah,
                'kelas' => $k->kelas,
                'semester' => $k->kelas?->semester?->tipe, // ganjil/genap
            ]),
        ]);
    }

    /**
     * Get mata kuliah list
     */
    protected function getMataKuliah($prodiId = null)
    {
        $query = MataKuliah::query();

        if ($prodiId) {
            $query->where('program_studi_id', $prodiId);
        }

        return response()->json([
            'data' => $query->orderBy('nama')->get()->map(fn($mk) => [
                'id' => $mk->id,
                'label' => $mk->nama,
                'info' => $mk->kode ?? '',
            ]),
        ]);
    }
}
