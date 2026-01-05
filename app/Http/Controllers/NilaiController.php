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

        // Determine access level based on permissions and user context
        // 1. Can View All? (Akademik, Admin) via 'nilai.approve' or 'nilai.do_everything' (conceptually)
        // We use 'nilai.approve' as a proxy for "Head/Admin" level access to grades.
        $canViewAll = $user->can('nilai.approve') || $user->hasRole('administrator');

        $query = KelasMatakuliah::query()
            ->with(['kelas.prodi', 'kelas.semester', 'mataKuliah', 'dosens.dosen']);

        $userProdiIds = $user->prodis()->pluck('program_studis.id');
        $dosenId = $user->dosen?->id;

        $userRole = 'dosen'; // Default fallback

        if ($canViewAll) {
            // Show all classes (Admin/Akademik)
            $userRole = 'admin'; // Or 'akademik', frontend treats them similarly for view all
            if ($user->can('nilai.approve'))
                $userRole = 'akademik';
            if ($user->hasRole('administrator'))
                $userRole = 'admin';
        } elseif ($userProdiIds->isNotEmpty()) {
            // Staff Prodi: filter by assigned prodis
            $query->whereHas('kelas', function ($q) use ($userProdiIds) {
                $q->whereIn('prodi_id', $userProdiIds);
            });
            $userRole = 'staff_prodi';
        } elseif ($dosenId) {
            // Dosen: filter by assigned classes
            $query->whereHas('dosens', function ($q) use ($dosenId) {
                $q->where('dosen_id', $dosenId);
            });
            $userRole = 'dosen';
        } else {
            // No access
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
            'userRole' => $userRole,
        ]);
    }

    public function show(KelasMatakuliah $kelasMatakuliah)
    {
        $user = Auth::user();
        $currentDosenId = $user->dosen?->id;
        $canViewAll = $user->can('nilai.approve') || $user->hasRole('administrator') || $user->isStaffProdi();

        // Load data needed for grading interface
        $kelasMatakuliah->load([
            'kelas.mahasiswas' => function ($q) {
                $q->orderBy('nama'); // Removed wherePivot filter - status might be null or different
            },
            'mataKuliah',
            'kelas.prodi', // Load prodi through kelas
            'dosens.dosen', // Load team teaching dosens
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

        // === TEAM TEACHING VISIBILITY LOGIC ===
        // Check if current dosen can view other dosen's grades
        $canViewOthersGrades = $canViewAll;
        if ($currentDosenId && !$canViewAll) {
            $settings = \App\Models\KelasMkNilaiSettings::where('kelas_matakuliah_id', $kelasMatakuliah->id)
                ->where('dosen_id', $currentDosenId)
                ->first();
            $canViewOthersGrades = $settings?->allow_view_others ?? false;
        }

        // Build scores query
        $scoresQuery = NilaiMahasiswa::where('kelas_matakuliah_id', $kelasMatakuliah->id)
            ->whereIn('komponen_nilai_id', $komponens->pluck('id'));

        // For dosen: filter by own grades OR if allowed to view others
        if ($currentDosenId && !$canViewOthersGrades) {
            $scoresQuery->where('dosen_id', $currentDosenId);
        }

        $scores = $scoresQuery->get()->groupBy('mahasiswa_id');

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
            // Team Teaching Context
            'currentDosenId' => $currentDosenId,
            'canViewOthersGrades' => $canViewOthersGrades,
            'canViewAll' => $canViewAll,
            'teamDosens' => $kelasMatakuliah->dosens->map(fn($d) => [
                'id' => $d->dosen_id,
                'nama' => $d->dosen->nama_gelar ?? $d->dosen->nama,
                'is_koordinator' => $d->is_koordinator,
            ]),
        ]);
    }

    public function store(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $request->validate([
            'grades' => 'required|array', // Structure: [{mahasiswa_id, component_id, nilai}]
            'action' => 'string|in:save,submit',
        ]);

        $action = $request->input('action', 'save');
        $user = Auth::user();
        $graderId = $user->id;
        $dosenId = $user->dosen?->id; // Get current dosen's ID for team teaching
        $kelasMatakuliah->load('kelas'); // Ensure kelas is loaded
        $prodiId = $kelasMatakuliah->kelas->prodi_id;
        $skalaNilais = SkalaNilai::forProdi($prodiId)->get();

        // Get components from Prodi, fallback to global
        $components = \App\Models\KomponenNilai::where('prodi_id', $prodiId)->where('is_active', true)->get();
        if ($components->isEmpty()) {
            $components = \App\Models\KomponenNilai::whereNull('prodi_id')->where('is_active', true)->get();
        }

        DB::transaction(function () use ($request, $kelasMatakuliah, $graderId, $dosenId, $skalaNilais, $components, $action) {

            // 1. Save Raw Scores (with dosen_id for team teaching)
            foreach ($request->grades as $g) {
                if (isset($g['nilai']) && $g['nilai'] !== null) {
                    NilaiMahasiswa::updateOrCreate(
                        [
                            'kelas_matakuliah_id' => $kelasMatakuliah->id,
                            'komponen_nilai_id' => $g['komponen_nilai_id'],
                            'mahasiswa_id' => $g['mahasiswa_id'],
                            'dosen_id' => $dosenId, // Include dosen_id in unique key for team teaching
                        ],
                        [
                            'nilai' => $g['nilai'],
                            'grader_id' => $graderId,
                            'status' => $action === 'submit' ? 'submitted' : 'draft',
                        ]
                    );
                }
            }

            // 2. Recalculate Final Grades for affected students (or all)
            // Group input by student to optimize
            $studentIds = collect($request->grades)->pluck('mahasiswa_id')->unique();

            foreach ($studentIds as $mhsId) {
                $totalScore = 0;

                // Fetch ALL scores for this student from ALL dosens (for team teaching averaging)
                $allStudentScores = NilaiMahasiswa::where('kelas_matakuliah_id', $kelasMatakuliah->id)
                    ->where('mahasiswa_id', $mhsId)
                    ->whereIn('komponen_nilai_id', $components->pluck('id'))
                    ->get()
                    ->groupBy('komponen_nilai_id');

                foreach ($components as $comp) {
                    $scoresForComponent = $allStudentScores->get($comp->id, collect());

                    if ($scoresForComponent->isEmpty()) {
                        $avgScore = 0;
                    } else {
                        // Average scores from all dosens who graded this component
                        $avgScore = $scoresForComponent->avg('nilai');
                    }

                    $totalScore += ($avgScore * $comp->bobot / 100);
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
        // Revert to Prodi-based lookup as kelas_matakuliah_id column is missing in DB
        $prodiId = $kelasMatakuliah->kelas->prodi_id;
        $komponens = \App\Models\KomponenNilai::where('prodi_id', $prodiId)->where('is_active', true)->get();
        if ($komponens->isEmpty()) {
            $komponens = \App\Models\KomponenNilai::whereNull('prodi_id')->where('is_active', true)->get();
        }

        $skalaNilais = SkalaNilai::forProdi($kelasMatakuliah->kelas->prodi_id)->orderBy('min_nilai', 'desc')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // === INFO HEADER (Rows 1-6) ===
        $mkKode = $kelasMatakuliah->mataKuliah->kode ?? '-';
        $mkNama = $kelasMatakuliah->mataKuliah->nama ?? '-';

        // Fetch Team Teaching Dosens
        $dosens = $kelasMatakuliah->dosens()->with('dosen')->get();
        if ($dosens->isNotEmpty()) {
            $dosenNama = $dosens->map(function ($d) {
                return $d->dosen->nama_gelar ?? $d->dosen->nama;
            })->unique()->join(' / ');
        } else {
            // Fallback to Main Class Dosen
            $dosenNama = $kelasMatakuliah->kelas->dosen->nama_gelar ?? ($kelasMatakuliah->kelas->dosen->nama ?? '-');
        }

        $prodiNama = $kelasMatakuliah->kelas->prodi->nama ?? '-';
        $semesterNama = $kelasMatakuliah->kelas->semester->nama ?? '-';
        $taNama = $kelasMatakuliah->kelas->semester->tahunAkademik->nama ?? '-';

        $info = [
            ['DAFTAR NILAI'],
            [''],
            ['KODE MK', ': ' . $mkKode],
            ['MATA KULIAH', ': ' . $mkNama],
            ['DOSEN', ': ' . $dosenNama],
            ['PROGRAM STUDI', ': ' . $prodiNama],
            ['SEMESTER / T.A.', ': ' . $semesterNama . ' / ' . $taNama],
            ['']
        ];

        $sheet->fromArray($info, NULL, 'A1');
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('C3:F3');
        $sheet->mergeCells('C4:F4');
        $sheet->mergeCells('C5:F5');
        $sheet->mergeCells('C6:F6');
        $sheet->mergeCells('C7:F7');

        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:A7')->getFont()->setBold(true);

        // === TABLE HEADER (Row 9) ===
        $headerRow = 9;
        $headers = ['NO', 'NIM', 'NAMA'];

        $currentColC = 'D';

        $compCols = []; // [compId => ColLetter]
        $kehadiranCol = null;

        foreach ($komponens as $comp) {
            $headers[] = $comp->nama . "\n(ID:" . $comp->id . ")\n" . $comp->bobot . '%';
            $compCols[$comp->id] = $currentColC;
            if ($comp->source_type === 'kehadiran') {
                $kehadiranCol = $currentColC;
            }
            $currentColC++;
        }

        $headers[] = "TOTAL";
        $totalCol = $currentColC;
        $currentColC++;

        $headers[] = "HURUF";
        $hurufCol = $currentColC;

        $sheet->fromArray([$headers], NULL, 'A' . $headerRow);

        // --- STYLING ---
        // Header Style
        $headerRange = 'A' . $headerRow . ':' . $hurufCol . $headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        // === PREPARE ATTENDANCE DATA ===
        // 1. Find Jadwal IDs for this KelasMK
        $jadwalIds = \App\Models\Jadwal::where('kelas_id', $kelasMatakuliah->kelas_id)
            ->where('mata_kuliah_id', $kelasMatakuliah->mata_kuliah_id)
            ->pluck('id');

        // 2. Count Total Pertemuan (Scheduled/Done)
        $totalPertemuan = \App\Models\JadwalPertemuan::whereIn('jadwal_id', $jadwalIds)
            ->whereIn('status', ['terjadwal', 'selesai'])
            ->count();

        // 3. Get Student Attendance Counts
        $studentAttendance = [];
        if ($totalPertemuan > 0) {
            $attendanceCounts = \App\Models\Absensi::whereIn('jadwal_pertemuan_id', function ($q) use ($jadwalIds) {
                $q->select('id')->from('jadwal_pertemuans')->whereIn('jadwal_id', $jadwalIds);
            })
                ->where('status', 'hadir')
                ->select('mahasiswa_id', DB::raw('count(*) as total'))
                ->groupBy('mahasiswa_id')
                ->pluck('total', 'mahasiswa_id');

            foreach ($attendanceCounts as $mhsId => $count) {
                $score = ($count / $totalPertemuan) * 100;
                $studentAttendance[$mhsId] = min(100, round($score, 2));
            }
        }

        // === DATA ===
        $rowIdx = $headerRow + 1;
        $no = 1;
        $kelasMatakuliah->load([
            'kelas.mahasiswas' => function ($q) {
                $q->orderBy('nama');
            }
        ]);

        // Formulas
        $formulaParts = [];
        foreach ($komponens as $comp) {
            $col = $compCols[$comp->id];
            $weight = $comp->bobot;
            $formulaParts[] = "{$col}{ROW}*{$weight}%";
        }
        $totalFormulaTpl = "=" . implode('+', $formulaParts);

        $hurufFormulaTpl = "=";
        $closingParens = "";
        foreach ($skalaNilais as $skala) {
            $hurufFormulaTpl .= "IF({$totalCol}{ROW}>={$skala->min_nilai},\"{$skala->huruf}\",";
            $closingParens .= ")";
        }
        $hurufFormulaTpl .= "\"E\"" . $closingParens;

        foreach ($kelasMatakuliah->kelas->mahasiswas as $mhs) {
            $rowHtml = [$no++, $mhs->nim, $mhs->nama];

            // Component Columns
            foreach ($komponens as $c) {
                // If Kehadiran, pre-fill
                if ($c->source_type === 'kehadiran') {
                    $score = $studentAttendance[$mhs->id] ?? 0;
                    $rowHtml[] = $score;
                } else {
                    $rowHtml[] = ''; // Manual Input
                }
            }

            // Formulas
            $rowHtml[] = str_replace('{ROW}', $rowIdx, $totalFormulaTpl);
            $rowHtml[] = str_replace('{ROW}', $rowIdx, $hurufFormulaTpl);

            $sheet->fromArray([$rowHtml], NULL, 'A' . $rowIdx);

            // Force NIM as String
            $sheet->getCell('B' . $rowIdx)->setValueExplicit($mhs->nim, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $rowIdx++;
        }

        // === COLUMN STYLING ===
        $lastRow = $rowIdx - 1;

        // 1. Read-Only Columns (Yellow): No, NIM, Nama
        $sheet->getStyle('A' . ($headerRow + 1) . ':C' . $lastRow)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFFE0'); // Light Yellow

        // 2. Input Columns (Green): Manual Components
        foreach ($compCols as $compId => $col) {
            $isKehadiran = false;
            foreach ($komponens as $k)
                if ($k->id == $compId && $k->source_type === 'kehadiran')
                    $isKehadiran = true;

            $range = $col . ($headerRow + 1) . ':' . $col . $lastRow;
            $color = $isKehadiran ? 'FFFFFFE0' : 'FFC6EFCE'; // Yellow if Kehadiran, Green if Manual

            $sheet->getStyle($range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB($color);
        }

        // 3. Calculated Columns (Yellow): Total, Huruf
        $sheet->getStyle($totalCol . ($headerRow + 1) . ':' . $hurufCol . $lastRow)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFFFE0');

        // Borders for Data
        if ($lastRow > $headerRow) {
            $sheet->getStyle('A' . ($headerRow + 1) . ':' . $hurufCol . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        // AutoSize
        foreach (range('A', $hurufCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Template_Nilai_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $kelasMatakuliah->kelas->nama) . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function importPreview(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $filePath = $request->file('file')->getPathname();

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal membaca file: ' . $e->getMessage()], 400);
        }

        if (empty($data))
            return response()->json(['error' => 'File kosong'], 400);

        // Find Header
        $headerRowIndex = null;
        $header = [];

        foreach ($data as $index => $row) {
            $rowString = implode(' ', array_map('strval', $row));
            if (stripos($rowString, 'NIM') !== false && stripos($rowString, 'ID:') !== false) {
                $headerRowIndex = $index;
                $header = $row;
                break;
            }
        }

        if ($headerRowIndex === null) {
            foreach ($data as $index => $row) {
                if (isset($row[1]) && stripos($row[1], 'NIM') !== false) {
                    $headerRowIndex = $index;
                    $header = $row;
                    break;
                }
            }
        }

        if ($headerRowIndex === null)
            return response()->json(['error' => 'Format header tidak dikenali. Pastikan kolom NIM ada.'], 400);

        // Parse Header
        $componentMap = [];
        $nimIndex = -1;

        foreach ($header as $index => $colName) {
            if ($colName) {
                if (preg_match('/ID:(\d+)/', $colName, $matches)) {
                    $componentMap[$index] = $matches[1];
                }
                if (stripos($colName, 'NIM') !== false) {
                    $nimIndex = $index;
                }
            }
        }

        if (empty($componentMap))
            return response()->json(['error' => 'Tidak ada komponen nilai (ID:xxx) ditemukan.'], 400);
        if ($nimIndex === -1)
            return response()->json(['error' => 'Kolom NIM tidak ditemukan.'], 400);

        $previewData = [];
        $mahasiswas = $kelasMatakuliah->kelas->mahasiswas->keyBy('nim');

        $dataRows = array_slice($data, $headerRowIndex + 1);

        foreach ($dataRows as $row) {
            $nim = isset($row[$nimIndex]) ? trim($row[$nimIndex]) : null;
            if (!$nim)
                continue;

            $mhs = $mahasiswas[$nim] ?? null;
            if (!$mhs)
                continue;

            $grades = [];
            foreach ($componentMap as $index => $compId) {
                $val = isset($row[$index]) ? $row[$index] : 0;
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

        $user = Auth::user();
        $graderId = $user->id;
        $dosenId = $user->dosen?->id; // Get dosen_id for team teaching
        $prodiId = $kelasMatakuliah->kelas->prodi_id;
        $skalaNilais = SkalaNilai::forProdi($prodiId)->orderBy('min_nilai', 'desc')->get();
        // Load relationships needed for re-calc
        $components = \App\Models\KomponenNilai::where('prodi_id', $prodiId)->where('is_active', true)->get();
        if ($components->isEmpty()) {
            $components = \App\Models\KomponenNilai::whereNull('prodi_id')->where('is_active', true)->get();
        }

        DB::beginTransaction();
        try {
            // Save imported grades (with dosen_id for team teaching)
            foreach ($validated['data'] as $item) {
                foreach ($item['grades'] as $compId => $val) {
                    NilaiMahasiswa::updateOrCreate(
                        [
                            'kelas_matakuliah_id' => $kelasMatakuliah->id,
                            'mahasiswa_id' => $item['mahasiswa_id'],
                            'komponen_nilai_id' => $compId,
                            'dosen_id' => $dosenId, // Include dosen_id for team teaching
                        ],
                        [
                            'nilai' => $val,
                            'grader_id' => $graderId,
                            'status' => 'draft',
                        ]
                    );
                }
            }

            // Recalculate Final Grades (averaging from all dosens)
            foreach ($validated['data'] as $item) {
                $mhsId = $item['mahasiswa_id'];
                $totalScore = 0;

                // Fetch ALL scores from ALL dosens for team teaching averaging
                $allStudentScores = NilaiMahasiswa::where('kelas_matakuliah_id', $kelasMatakuliah->id)
                    ->where('mahasiswa_id', $mhsId)
                    ->whereIn('komponen_nilai_id', $components->pluck('id'))
                    ->get()
                    ->groupBy('komponen_nilai_id');

                foreach ($components as $comp) {
                    $scoresForComponent = $allStudentScores->get($comp->id, collect());
                    $avgScore = $scoresForComponent->isEmpty() ? 0 : $scoresForComponent->avg('nilai');
                    $totalScore += ($avgScore * $comp->bobot / 100);
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
