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
                $q->orderBy('nama'); // Removed wherePivot filter - status might be null or different
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

        // === ATTENDANCE DATA ===
        // Get all JadwalPertemuan for this KelasMatakuliah's MK + Kelas combo
        $jadwalPertemuans = \App\Models\JadwalPertemuan::whereHas('jadwal', function ($q) use ($kelasMatakuliah) {
            $q->where('mata_kuliah_id', $kelasMatakuliah->mata_kuliah_id)
                ->where('kelas_id', $kelasMatakuliah->kelas_id);
        })
            ->with('absensis')
            ->orderBy('tanggal')
            ->orderBy('pertemuan_ke')
            ->get();

        // Calculate attendance summary per mahasiswa
        $attendanceSummary = [];
        $mahasiswaIds = $kelasMatakuliah->kelas->mahasiswas->pluck('id');
        $totalMeetings = $jadwalPertemuans->count();

        foreach ($mahasiswaIds as $mhsId) {
            $hadirCount = 0;
            $izinCount = 0;
            $sakitCount = 0;
            $alphaCount = 0;

            foreach ($jadwalPertemuans as $jp) {
                $absensi = $jp->absensis->firstWhere('mahasiswa_id', $mhsId);
                if ($absensi) {
                    match ($absensi->status) {
                        'hadir' => $hadirCount++,
                        'izin' => $izinCount++,
                        'sakit' => $sakitCount++,
                        'alpha' => $alphaCount++,
                        default => null,
                    };
                }
            }

            $attendanceSummary[$mhsId] = [
                'hadir' => $hadirCount,
                'izin' => $izinCount,
                'sakit' => $sakitCount,
                'alpha' => $alphaCount,
                'percent' => $totalMeetings > 0 ? round(($hadirCount / $totalMeetings) * 100, 1) : 0,
            ];
        }

        // Build attendance detail per pertemuan
        $attendanceDetail = $jadwalPertemuans->map(function ($jp) {
            return [
                'id' => $jp->id,
                'pertemuan_ke' => $jp->pertemuan_ke,
                'tanggal' => $jp->tanggal?->format('Y-m-d'),
                'absensis' => $jp->absensis->keyBy('mahasiswa_id')->map(fn($a) => $a->status),
            ];
        });

        return Inertia::render('Kelas/Nilai/Show', [
            'kelasMatakuliah' => $kelasMatakuliah,
            'mahasiswas' => $kelasMatakuliah->kelas->mahasiswas,
            'komponens' => $komponens,
            'scores' => $scores,
            'rekaps' => $rekaps,
            'skalaNilais' => $skalaNilais,
            'attendanceSummary' => $attendanceSummary,
            'attendanceDetail' => $attendanceDetail,
            'totalMeetings' => $totalMeetings,
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
    public function downloadTemplate(KelasMatakuliah $kelasMatakuliah)
    {
        $prodiId = $kelasMatakuliah->kelas->prodi_id;
        $komponens = \App\Models\KomponenNilai::where('prodi_id', $prodiId)->where('is_active', true)->get();
        if ($komponens->isEmpty()) {
            $komponens = \App\Models\KomponenNilai::whereNull('prodi_id')->where('is_active', true)->get();
        }

        $filename = 'Template_Nilai_' . $kelasMatakuliah->kelas->nama . '_' . ($kelasMatakuliah->mataKuliah->kode ?? 'MK') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\NilaiTemplateExport($kelasMatakuliah, $komponens), $filename);
    }

    public function importPreview(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $request->file('file'));
        if (empty($data))
            return response()->json(['error' => 'File kosong'], 400);

        $rows = $data[0]; // Sheet 1
        $header = array_shift($rows); // Row 1

        // Parse Header to find Component IDs
        $componentMap = []; // index => components_id
        foreach ($header as $index => $colName) {
            // Check for ID in format "Name (ID:123)"
            if (preg_match('/\(ID:(\d+)\)/', $colName, $matches)) {
                $componentMap[$index] = $matches[1];
            }
        }

        if (empty($componentMap)) {
            return response()->json(['error' => 'Format header tidak valid. Pastikan menggunakan template yang disediakan.'], 400);
        }

        $previewData = [];
        $mahasiswas = $kelasMatakuliah->kelas->mahasiswas->keyBy('nim');

        foreach ($rows as $row) {
            $nim = isset($row[1]) ? trim($row[1]) : null; // Column B (Index 1) based on template: No, NIM, Nama
            if (!$nim)
                continue;

            $mhs = $mahasiswas[$nim] ?? null;
            if (!$mhs)
                continue;

            $grades = [];
            foreach ($componentMap as $index => $compId) {
                // Determine grade value
                $val = isset($row[$index]) ? $row[$index] : 0;
                // If cell is empty, default to 0
                if ($val === null || $val === '')
                    $val = 0;

                $val = is_numeric($val) ? floatval($val) : 0;

                if ($val < 0)
                    $val = 0;
                if ($val > 100)
                    $val = 100;

                $grades[$compId] = $val;
            }

            $previewData[] = [
                'mahasiswa_id' => $mhs->id,
                'nim' => $mhs->nim,
                'nama' => $mhs->nama,
                'grades' => $grades,
            ];
        }

        return response()->json([
            'preview' => $previewData,
            'component_ids' => array_values($componentMap)
        ]);
    }

    public function importStore(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.mahasiswa_id' => 'required|exists:mahasiswas,id',
            'data.*.grades' => 'required|array',
        ]);

        $graderId = Auth::id();
        $prodiId = $kelasMatakuliah->kelas->prodi_id;
        $skalaNilais = SkalaNilai::forProdi($prodiId)->get();

        $components = \App\Models\KomponenNilai::where('prodi_id', $prodiId)->where('is_active', true)->get();
        if ($components->isEmpty()) {
            $components = \App\Models\KomponenNilai::whereNull('prodi_id')->where('is_active', true)->get();
        }

        DB::beginTransaction();
        try {
            // 1. Save Grades
            foreach ($validated['data'] as $item) {
                foreach ($item['grades'] as $compId => $val) {
                    NilaiMahasiswa::updateOrCreate(
                        [
                            'mahasiswa_id' => $item['mahasiswa_id'],
                            'kelas_mata_kuliah_id' => $kelasMatakuliah->id,
                            'komponen_nilai_id' => $compId,
                        ],
                        [
                            'nilai' => $val,
                            'grader_id' => $graderId,
                        ]
                    );
                }
            }

            // 2. Recalculate Final Grades for affected students
            foreach ($validated['data'] as $item) {
                $mhsId = $item['mahasiswa_id'];

                $totalScore = 0;
                // Fetch updated scores
                $studentScores = NilaiMahasiswa::where('mahasiswa_id', $mhsId)
                    ->whereIn('komponen_nilai_id', $components->pluck('id'))
                    ->get()
                    ->keyBy('komponen_nilai_id');

                foreach ($components as $comp) {
                    $score = isset($studentScores[$comp->id]) ? $studentScores[$comp->id]->nilai : 0;
                    $totalScore += ($score * $comp->bobot / 100);
                }

                $gradeLetter = 'E';
                foreach ($skalaNilais as $skala) {
                    if ($totalScore >= $skala->min_nilai && $totalScore <= $skala->max_nilai) {
                        $gradeLetter = $skala->huruf;
                        break;
                    }
                }

                RekapNilai::updateOrCreate(
                    [
                        'kelas_matakuliah_id' => $kelasMatakuliah->id,
                        'mahasiswa_id' => $mhsId,
                    ],
                    [
                        'nilai_angka' => $totalScore,
                        'nilai_huruf' => $gradeLetter,
                        // 'nilai_indeks' => ... (omitted for brevity, can happen later)
                    ]
                );
            }

            DB::commit();
            return back()->with('success', 'Nilai berhasil diimport!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => 'Gagal import: ' . $e->getMessage()]);
        }
    }
}
