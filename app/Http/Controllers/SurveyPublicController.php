<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\KelasMatakuliah;
use App\Models\Mahasiswa;
use App\Models\SurveyAnswer;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SurveyPublicController extends Controller
{
    /**
     * Show public survey form
     */
    public function show(Request $request, SurveyPeriod $period)
    {
        // Check if period is active
        if ($period->status !== 'active') { // Assuming 'published' is the active status or check dates
            // You might want to check start_date and end_date here too
            // For now trusting the status or adding date check:
            $now = now();
            if ($period->start_date > $now || $period->end_date < $now) {
                return Inertia::render('Survey/Public/Closed', [
                    'period' => $period
                ]);
            }
        }

        $period->load([
            'template.questions' => function ($q) {
                $q->orderBy('urutan');
            }
        ]);

        // If user is logged in as mahasiswa
        $mahasiswa = null;
        if (auth()->guard('mahasiswa')->check()) {
            $mahasiswa = auth()->guard('mahasiswa')->user();
        }

        // Get Program Studi list for dropdown
        $programStudis = \App\Models\ProgramStudi::orderBy('nama')->get(['id', 'nama']);

        return Inertia::render('Survey/Public/Fill', [
            'period' => $period,
            'questions' => $period->template->questions ?? [],
            'programStudis' => $programStudis,
            'isLoggedIn' => $mahasiswa ? true : false,
            'mahasiswa' => $mahasiswa,
        ]);
    }

    /**
     * Store public survey response (Single submit for multiple lecturers)
     */
    public function store(Request $request, SurveyPeriod $period)
    {
        // Validate period is active
        if ($period->status !== 'active') {
            return back()->with('error', 'Survei tidak aktif');
        }

        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'kelas_matakuliah_id' => 'required|exists:kelas_matakuliah,id',
            'responses' => 'required|array|min:1',
            'responses.*.dosen_id' => 'required|exists:dosens,id',
            'responses.*.answers' => 'required|array',
        ]);

        try {
            DB::transaction(function () use ($period, $validated) {
                foreach ($validated['responses'] as $respData) {
                    // Check if already responded for this specific dosen
                    $exists = SurveyResponse::where('survey_period_id', $period->id)
                        ->where('mahasiswa_id', $validated['mahasiswa_id'])
                        ->where('dosen_id', $respData['dosen_id'])
                        ->where('kelas_matakuliah_id', $validated['kelas_matakuliah_id'])
                        ->exists();

                    if ($exists) {
                        continue; // Skip if already filled for this dosen
                    }

                    // Create response header
                    $response = SurveyResponse::create([
                        'survey_period_id' => $period->id,
                        'mahasiswa_id' => $validated['mahasiswa_id'],
                        'dosen_id' => $respData['dosen_id'],
                        'kelas_matakuliah_id' => $validated['kelas_matakuliah_id'],
                        'submitted_at' => now(),
                    ]);

                    // Create answers details
                    foreach ($respData['answers'] as $questionId => $value) {
                        $answerData = [
                            'survey_response_id' => $response->id,
                            'survey_question_id' => $questionId,
                        ];

                        if (is_numeric($value)) {
                            // Rating/Scale
                            $answerData['rating_value'] = $value;
                        } else {
                            // Text/Essay
                            $answerData['text_value'] = $value;
                        }

                        SurveyAnswer::create($answerData);
                    }
                }
            });



            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Terima kasih! Survei Anda telah berhasil dikirim.',
                    'status' => 'success'
                ]);
            }

            return back()->with('success', 'Terima kasih! Survei Anda telah berhasil dikirim.');
        } catch (\Illuminate\Database\QueryException $e) {
            $message = 'Terjadi kesalahan database.';
            if ($e->errorInfo[1] == 1062) {
                $message = 'Anda sudah mengisi survei untuk dosen ini sebelumnya.';
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }
            return back()->with('error', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    // ==========================================
    // API Endpoints for Cascading Dropdown
    // ==========================================

    /**
     * Get Kelas list by Program Studi
     */
    public function getKelasList(Request $request, SurveyPeriod $period)
    {
        $prodiId = $request->input('prodi_id');

        if (!$prodiId) {
            return response()->json(['data' => []]);
        }

        // Get Kelas for this prodi and period's tahun akademik
        $kelas = \App\Models\Kelas::with(['semester'])
            ->where('prodi_id', $prodiId)
            ->whereHas('semester', fn($q) => $q->where('tahun_akademik_id', $period->tahun_akademik_id))
            ->get();

        return response()->json([
            'data' => $kelas->map(fn($k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'semester' => $k->semester->nama ?? '',
            ])
        ]);
    }

    /**
     * Get Mata Kuliah by Kelas
     */
    public function getMatakuliahByKelas(Request $request, SurveyPeriod $period)
    {
        $kelasId = $request->input('kelas_id');

        if (!$kelasId) {
            return response()->json(['data' => []]);
        }

        // Get KelasMatakuliah for this Kelas
        $kelasMatakuliahs = KelasMatakuliah::with('mataKuliah')
            ->where('kelas_id', $kelasId)
            ->get();

        return response()->json([
            'data' => $kelasMatakuliahs->map(fn($k) => [
                'id' => $k->id,
                'nama' => $k->mataKuliah->nama ?? '',
                'kode' => $k->mataKuliah->kode ?? '',
            ])
        ]);
    }

    /**
     * Get Mahasiswa by Kelas
     */
    public function getMahasiswa(Request $request, SurveyPeriod $period)
    {
        $kelasId = $request->input('kelas_id');

        if (!$kelasId) {
            return response()->json(['data' => []]);
        }

        // Get mahasiswa enrolled in this Kelas
        $kelas = \App\Models\Kelas::with('mahasiswas')->find($kelasId);

        if (!$kelas) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $kelas->mahasiswas->map(fn($m) => [
                'id' => $m->id,
                'nama' => $m->nama,
                'nim' => $m->nim,
            ])->sortBy('nama')->values()
        ]);
    }

    /**
     * Get Dosen by KelasMatakuliah (for team teaching)
     * Also checks if the specific mahasiswa has already filled survey for each dosen.
     */
    public function getDosenByKelas(Request $request, SurveyPeriod $period)
    {
        $kelasMkId = $request->input('kelas_matakuliah_id');
        $mahasiswaId = $request->input('mahasiswa_id');

        if (!$kelasMkId) {
            return response()->json(['data' => []]);
        }

        $kelasMk = KelasMatakuliah::with('dosens.dosen')->find($kelasMkId);

        if (!$kelasMk) {
            return response()->json(['data' => []]);
        }

        $dosens = $kelasMk->dosens->map(fn($d) => [
            'id' => $d->dosen_id,
            'nama' => $d->dosen->nama ?? '',
            'nidn' => $d->dosen->nidn ?? '',
        ])->unique('id')->values();

        // Check completion status if mahasiswa_id provided
        if ($mahasiswaId) {
            $dosens = $dosens->map(function ($d) use ($period, $mahasiswaId, $kelasMkId) {
                $hasResponse = SurveyResponse::where('survey_period_id', $period->id)
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->where('dosen_id', $d['id'])
                    ->where('kelas_matakuliah_id', $kelasMkId)
                    ->exists();

                $d['is_filled'] = $hasResponse;
                return $d;
            });
        }

        return response()->json(['data' => $dosens]);
    }
}
