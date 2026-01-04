<?php

namespace App\Http\Controllers;

use App\Models\KelasMatakuliah;
use App\Models\NilaiMahasiswa;
use App\Models\RekapNilai;
use App\Models\SkalaNilai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NilaiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Determine user type and filter accordingly
        $isAdmin = $user->hasRole('admin') || $user->hasRole('super-admin');
        $isAkademik = $user->isAkademik();
        $isStaffProdi = $user->isStaffProdi() && !$isAkademik;
        $isDosen = $user->isDosen() && !$isAkademik && !$isStaffProdi;

        // Get dosen_id if user is linked to dosen
        $dosenId = $user->dosen?->id;

        $query = KelasMatakuliah::query()
            ->with(['kelas.prodi', 'kelas.semester', 'mataKuliah', 'dosens.dosen']);

        // Apply filters based on user role
        if ($isDosen && $dosenId) {
            // Dosen: only show their classes
            $query->whereHas('dosens', function ($q) use ($dosenId) {
                $q->where('dosen_id', $dosenId);
            });
        } elseif ($isStaffProdi) {
            // Staff Prodi: show classes in their prodi
            $userProdiIds = $user->prodis()->pluck('program_studis.id');
            $query->whereHas('kelas', function ($q) use ($userProdiIds) {
                $q->whereIn('prodi_id', $userProdiIds);
            });
        } elseif ($isAdmin || $isAkademik) {
            // Admin/Akademik: show all classes (no filter)
        } else {
            // Unknown role - show nothing
            $query->whereRaw('1 = 0');
        }

        // Filter by active semester only
        $query->whereHas('kelas.semester', function ($q) {
            $q->where('is_active', true);
        });

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('mataKuliah', function ($sub) use ($request) {
                    $sub->where('nama', 'like', "%{$request->search}%")
                        ->orWhere('kode', 'like', "%{$request->search}%");
                })->orWhereHas('kelas', function ($sub) use ($request) {
                    $sub->where('nama', 'like', "%{$request->search}%");
                });
            });
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Kelas/Nilai/Index', [
            'items' => $items,
            'filters' => $request->only(['search']),
            'userRole' => $isAdmin ? 'admin' : ($isAkademik ? 'akademik' : ($isStaffProdi ? 'staff_prodi' : 'dosen')),
        ]);
    }

    public function show(KelasMatakuliah $kelasMatakuliah)
    {
        // Load data needed for grading interface
        $kelasMatakuliah->load([
            'kelas.mahasiswas' => function ($q) {
                $q->wherePivot('status', 'active')->orderBy('nama');
            },
            'mataKuliah',
            'kelas.prodi', // Load prodi through kelas
        ]);

        $prodiId = $kelasMatakuliah->kelas->prodi_id;

        // Get Komponen Nilai from Prodi, fallback to Global if not defined
        $komponens = \App\Models\KomponenNilai::where('prodi_id', $prodiId)
            ->where('is_active', true)
            ->get();

        // Fallback to global components if Prodi has none
        if ($komponens->isEmpty()) {
            $komponens = \App\Models\KomponenNilai::whereNull('prodi_id')
                ->where('is_active', true)
                ->get();
        }

        // Get existing scores
        $scores = NilaiMahasiswa::whereIn('komponen_nilai_id', $komponens->pluck('id'))
            ->get()
            ->groupBy('mahasiswa_id');

        // Get Rekap (Final Grades)
        $rekaps = RekapNilai::where('kelas_matakuliah_id', $kelasMatakuliah->id)
            ->get()
            ->keyBy('mahasiswa_id');

        // Get Skala Nilai (Prodi or Global)
        $skalaNilais = SkalaNilai::forProdi($prodiId)->get();

        return Inertia::render('Kelas/Nilai/Show', [
            'kelasMatakuliah' => $kelasMatakuliah,
            'mahasiswas' => $kelasMatakuliah->kelas->mahasiswas,
            'komponens' => $komponens,
            'scores' => $scores,
            'rekaps' => $rekaps,
            'skalaNilais' => $skalaNilais,
        ]);
    }

    public function store(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $request->validate([
            'grades' => 'required|array', // Structure: [{mahasiswa_id, component_id, nilai}]
            'action' => 'string|in:save,submit',
        ]);

        $action = $request->input('action', 'save');
        $graderId = Auth::id();
        $kelasMatakuliah->load('kelas'); // Ensure kelas is loaded
        $prodiId = $kelasMatakuliah->kelas->prodi_id;
        $skalaNilais = SkalaNilai::forProdi($prodiId)->get();

        // Get components from Prodi, fallback to global
        $components = \App\Models\KomponenNilai::where('prodi_id', $prodiId)->where('is_active', true)->get();
        if ($components->isEmpty()) {
            $components = \App\Models\KomponenNilai::whereNull('prodi_id')->where('is_active', true)->get();
        }

        DB::transaction(function () use ($request, $kelasMatakuliah, $graderId, $skalaNilais, $components, $action) {

            // 1. Save Raw Scores
            foreach ($request->grades as $g) {
                if (isset($g['nilai']) && $g['nilai'] !== null) {
                    NilaiMahasiswa::updateOrCreate(
                        [
                            'komponen_nilai_id' => $g['komponen_nilai_id'],
                            'mahasiswa_id' => $g['mahasiswa_id'],
                        ],
                        [
                            'nilai' => $g['nilai'],
                            'grader_id' => $graderId,
                        ]
                    );
                }
            }

            // 2. Recalculate Final Grades for affected students (or all)
            // Group input by student to optimize
            $studentIds = collect($request->grades)->pluck('mahasiswa_id')->unique();

            foreach ($studentIds as $mhsId) {
                $totalScore = 0;

                // Fetch fresh scores for this student
                $studentScores = NilaiMahasiswa::where('mahasiswa_id', $mhsId)
                    ->whereIn('komponen_nilai_id', $components->pluck('id'))
                    ->get()
                    ->keyBy('komponen_nilai_id');

                foreach ($components as $comp) {
                    $score = isset($studentScores[$comp->id]) ? $studentScores[$comp->id]->nilai : 0;
                    $totalScore += ($score * $comp->bobot / 100);
                }

                // Determine Grade Letter
                $gradeLetter = 'E';
                $gradeIndex = 0.00;

                foreach ($skalaNilais as $skala) {
                    if ($totalScore >= $skala->min_nilai && $totalScore <= $skala->max_nilai) {
                        $gradeLetter = $skala->huruf;
                        $gradeIndex = $skala->bobot;
                        break;
                    }
                }
                // Handle edge case > 100 or rounding context? 
                // Usually logic is >= Min. Max is cap. 

                // Save Rekap
                $rekapData = [
                    'nilai_angka' => $totalScore,
                    'nilai_huruf' => $gradeLetter,
                    'nilai_indeks' => $gradeIndex,
                ];

                if ($action === 'submit') {
                    $rekapData['status'] = 'submitted';
                }

                RekapNilai::updateOrCreate(
                    [
                        'kelas_matakuliah_id' => $kelasMatakuliah->id,
                        'mahasiswa_id' => $mhsId,
                    ],
                    $rekapData
                );
            }
        });

        return redirect()->back()->with('success', 'Nilai berhasil disimpan' . ($action === 'submit' ? ' dan disubmit.' : '.'));
    }
}
