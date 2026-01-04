<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\JadwalPertemuan;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AbsensiController extends Controller
{
    /**
     * Main Absensi page - shows list of classes/meetings for attendance input
     * This is the landing page from the sidebar menu
     */
    public function indexPage(Request $request)
    {
        $user = auth()->user();

        // Check if user is a dosen (not admin/staff)
        $isDosen = $user->isDosen() && !$user->isAkademik() && !$user->isStaffProdi();
        $dosenId = $isDosen ? $user->dosen?->id : null;

        // Date range filter (default: last 7 days to today)
        $tanggalAwal = $request->input('tanggal_awal', now()->subDays(7)->toDateString());
        $tanggalAkhir = $request->input('tanggal_akhir', now()->toDateString());

        // Get all classes with upcoming meetings
        // For dosen, only their classes; for admin/staff, all classes
        $kelasQuery = \App\Models\Kelas::query()
            ->with([
                'semester',
                'prodi',
                'kelasMatakuliahs.mataKuliah',
            ])
            ->withCount('mahasiswas')
            ->whereHas('semester', function ($q) {
                $q->where('is_active', true);
            });

        // Prodi Logic
        $user = auth()->user();
        $isStaffProdi = $user->isStaffProdi() && !$user->isAkademik();
        $userProdiIds = $isStaffProdi ? $user->prodis()->pluck('program_studis.id') : [];

        if ($isStaffProdi) {
            $kelasQuery->whereIn('prodi_id', $userProdiIds);
        }

        // If user is a dosen, filter by their assigned classes
        if ($dosenId) {
            $kelasQuery->whereHas('kelasMatakuliahs.dosens', function ($q) use ($dosenId) {
                $q->where('dosen_id', $dosenId);
            });
        }

        $kelases = $kelasQuery->orderBy('nama')->get();

        // Get meetings within date range
        $meetingsQuery = \App\Models\JadwalPertemuan::query()
            ->with([
                'jadwal.mataKuliah',
                'jadwal.kelasModel',
                'dosen',
                'ruangan', // Room override
                'jadwal.ruangan', // Default room
            ])
            ->withCount('absensis')
            ->whereHas('jadwal.kelasModel.semester', function ($q) {
                $q->where('is_active', true);
            })
            ->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir]);

        // Filter by prodi if staff prodi
        if ($isStaffProdi) {
            $meetingsQuery->whereHas('jadwal.kelasModel', function ($q) use ($userProdiIds) {
                $q->whereIn('prodi_id', $userProdiIds);
            });
        }

        // Filter by dosen if user is a dosen (not admin/staff)
        if ($dosenId) {
            $meetingsQuery->where('dosen_id', $dosenId);
        }

        $meetings = $meetingsQuery
            ->orderBy('tanggal')
            ->get()
            ->map(function ($meeting) {
                $meeting->has_attendance = $meeting->absensis_count > 0;
                return $meeting;
            });

        return Inertia::render('Absensi/Index', [
            'kelases' => $kelases,
            'meetings' => $meetings,
            'isDosen' => $isDosen,
            'filters' => [
                'tanggal_awal' => $tanggalAwal,
                'tanggal_akhir' => $tanggalAkhir,
            ],
        ]);
    }

    /**
     * Show attendance + jurnal page for a specific meeting (tabbed interface)
     */
    public function index(JadwalPertemuan $jadwalPertemuan)
    {
        $jadwalPertemuan->load([
            'jadwal.mataKuliah',
            'jadwal.kelasModel.mahasiswas',
            'dosen',
            'ruangan',
            'absensis.mahasiswa',
            'jurnal'
        ]);

        // Get the Kelas model properly (use kelasModel to avoid column name conflict)
        $kelas = $jadwalPertemuan->jadwal?->kelasModel;

        if (!$kelas) {
            // Fallback: try to get kelas from kelas_id
            $kelas = \App\Models\Kelas::find($jadwalPertemuan->jadwal?->kelas_id);
        }

        if (!$kelas) {
            return back()->withErrors(['error' => 'Kelas tidak ditemukan untuk jadwal ini.']);
        }

        // Get all students enrolled in this class
        $mahasiswas = $kelas->mahasiswas()
            ->orderBy('nama')
            ->get()
            ->map(function ($mhs) use ($jadwalPertemuan) {
                // Check existing attendance
                $absensi = $jadwalPertemuan->absensis->where('mahasiswa_id', $mhs->id)->first();
                return [
                    'id' => $mhs->id,
                    'nim' => $mhs->nim,
                    'nama' => $mhs->nama,
                    'status' => $absensi?->status ?? 'hadir',
                    'keterangan' => $absensi?->keterangan ?? '',
                    'jam_masuk' => $absensi?->jam_masuk ?? null,
                    'jam_keluar' => $absensi?->jam_keluar ?? null,
                    'absensi_id' => $absensi?->id,
                ];
            });

        // Get existing jurnal or empty data
        $jurnal = $jadwalPertemuan->jurnal;

        return Inertia::render('Absensi/Input', [
            'jadwalPertemuan' => $jadwalPertemuan,
            'mataKuliah' => $jadwalPertemuan->jadwal?->mataKuliah,
            'kelas' => $kelas,
            'mahasiswas' => $mahasiswas,
            'jurnal' => $jurnal,
        ]);
    }

    /**
     * Bulk save attendance for a meeting
     */
    public function store(Request $request, JadwalPertemuan $jadwalPertemuan)
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.mahasiswa_id' => 'required|exists:mahasiswas,id',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpha',
            'attendances.*.keterangan' => 'nullable|string|max:500',
            'attendances.*.jam_masuk' => 'nullable|string', // Accept any time string
            'attendances.*.jam_keluar' => 'nullable|string', // Accept any time string
        ]);

        foreach ($validated['attendances'] as $attendance) {
            Absensi::updateOrCreate(
                [
                    'jadwal_pertemuan_id' => $jadwalPertemuan->id,
                    'mahasiswa_id' => $attendance['mahasiswa_id'],
                ],
                [
                    'status' => $attendance['status'],
                    'keterangan' => $attendance['keterangan'] ?? null,
                    'jam_masuk' => $attendance['jam_masuk'] ?? null,
                    'jam_keluar' => $attendance['jam_keluar'] ?? null,
                    'input_by' => auth()->id(),
                ]
            );
        }

        return back()->with('success', 'Absensi berhasil disimpan');
    }

    /**
     * Store jurnal perkuliahan for a meeting
     */
    public function storeJurnal(Request $request, JadwalPertemuan $jadwalPertemuan)
    {
        $validated = $request->validate([
            'materi' => 'nullable|string|max:1000',
            'aktivitas' => 'nullable|string|max:1000',
            'capaian' => 'nullable|string|max:1000',
            'catatan' => 'nullable|string|max:1000',
            'file_materi' => 'nullable|array',
            'file_materi.*' => 'file|mimes:pdf,ppt,pptx|max:20480', // Max 20MB per file
            'deleted_files' => 'nullable|array', // Files to remove
        ]);

        $user = auth()->user();
        $dosenId = $user->dosen?->id ?? $jadwalPertemuan->dosen_id;

        // Count attendance summary
        $jadwalPertemuan->load('absensis');
        $absensis = $jadwalPertemuan->absensis;
        $jumlahHadir = $absensis->where('status', 'hadir')->count();
        $jumlahIzin = $absensis->where('status', 'izin')->count();
        $jumlahSakit = $absensis->where('status', 'sakit')->count();
        $jumlahAlpha = $absensis->where('status', 'alpha')->count();

        // Get existing files
        $existingFiles = $jadwalPertemuan->jurnal?->file_materi ?? [];

        // Remove deleted files
        if (!empty($validated['deleted_files'])) {
            foreach ($validated['deleted_files'] as $deletedFile) {
                $existingFiles = array_filter($existingFiles, fn($f) => $f !== $deletedFile);
                // Optionally delete the actual file
                \Storage::disk('public')->delete($deletedFile);
            }
            $existingFiles = array_values($existingFiles); // Re-index
        }

        // Handle new file uploads
        $newFiles = [];
        if ($request->hasFile('file_materi')) {
            foreach ($request->file('file_materi') as $file) {
                $path = $file->store('jurnal', 'public');
                $newFiles[] = $path;
            }
        }

        // Merge existing and new files
        $allFiles = array_merge($existingFiles, $newFiles);

        \App\Models\Jurnal::updateOrCreate(
            ['jadwal_pertemuan_id' => $jadwalPertemuan->id],
            [
                'materi' => $validated['materi'],
                'aktivitas' => $validated['aktivitas'],
                'capaian' => $validated['capaian'],
                'catatan' => $validated['catatan'],
                'file_materi' => $allFiles,
                'dosen_id' => $dosenId,
                'jumlah_hadir' => $jumlahHadir,
                'jumlah_izin' => $jumlahIzin,
                'jumlah_sakit' => $jumlahSakit,
                'jumlah_alpha' => $jumlahAlpha,
            ]
        );

        // Auto-mark dosen as present when jurnal is saved
        if (!$jadwalPertemuan->dosen_hadir) {
            $jadwalPertemuan->update([
                'dosen_hadir' => true,
                'dosen_jam_masuk' => $jadwalPertemuan->dosen_jam_masuk ?? $jadwalPertemuan->jam_mulai,
                'dosen_jam_keluar' => $jadwalPertemuan->dosen_jam_keluar ?? $jadwalPertemuan->jam_selesai,
            ]);
        }

        return back()->with('success', 'Jurnal perkuliahan berhasil disimpan');
    }

    /**
     * Store dosen teaching attendance (jam mengajar)
     */
    public function storeDosenAttendance(Request $request, JadwalPertemuan $jadwalPertemuan)
    {
        $validated = $request->validate([
            'dosen_jam_masuk' => 'nullable',
            'dosen_jam_keluar' => 'nullable',
        ]);

        // Only update fields that are provided
        $updateData = ['dosen_hadir' => true];
        if (!empty($validated['dosen_jam_masuk'])) {
            $updateData['dosen_jam_masuk'] = $validated['dosen_jam_masuk'];
        }
        if (!empty($validated['dosen_jam_keluar'])) {
            $updateData['dosen_jam_keluar'] = $validated['dosen_jam_keluar'];
        }

        $jadwalPertemuan->update($updateData);

        return back()->with('success', 'Absensi dosen berhasil disimpan');
    }

    /**
     * Reset dosen attendance
     */
    public function resetDosenAttendance(JadwalPertemuan $jadwalPertemuan)
    {
        $jadwalPertemuan->update([
            'dosen_jam_masuk' => null,
            'dosen_jam_keluar' => null,
            'dosen_hadir' => false,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get absensis for a meeting (API/JSON)
     */
    public function getAbsensisJson(JadwalPertemuan $jadwalPertemuan)
    {
        $jadwalPertemuan->load(['jadwal.kelasModel.mahasiswas', 'absensis']);

        $mahasiswas = $jadwalPertemuan->jadwal?->kelasModel?->mahasiswas ?? collect([]);
        $absensis = $jadwalPertemuan->absensis->keyBy('mahasiswa_id');

        $result = $mahasiswas->map(function ($mhs) use ($absensis) {
            $absensi = $absensis->get($mhs->id);
            return [
                'id' => $mhs->id,
                'nim' => $mhs->nim,
                'nama' => $mhs->nama,
                'status' => $absensi?->status ?? null,
                'jam_masuk' => $absensi?->jam_masuk,
                'jam_keluar' => $absensi?->jam_keluar,
            ];

        });

        return response()->json($result->values());
    }

    /**
     * Update single mahasiswa status (API)
     */
    public function updateMahasiswaStatus(Request $request, JadwalPertemuan $jadwalPertemuan)
    {
        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'status' => 'required|in:hadir,izin,sakit,alpha,null', // string 'null' for reset
        ]);

        if ($validated['status'] === 'null') {
            \App\Models\Absensi::where('jadwal_pertemuan_id', $jadwalPertemuan->id)
                ->where('mahasiswa_id', $validated['mahasiswa_id'])
                ->delete();
        } else {
            $absensi = \App\Models\Absensi::withTrashed()->updateOrCreate(
                [
                    'jadwal_pertemuan_id' => $jadwalPertemuan->id,
                    'mahasiswa_id' => $validated['mahasiswa_id']
                ],
                [
                    'status' => $validated['status'],
                    'jam_masuk' => $validated['status'] == 'hadir' ? now()->format('H:i') : null,
                ]
            );
            if ($absensi->trashed()) {
                $absensi->restore();
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Bulk update mahasiswa status (API)
     */
    public function bulkUpdateMahasiswaStatus(Request $request, JadwalPertemuan $jadwalPertemuan)
    {
        $validated = $request->validate([
            'status' => 'required|in:hadir,izin,sakit,alpha,null',
        ]);

        // Ensure relationships loaded to get all students
        $jadwalPertemuan->load('jadwal.kelasModel.mahasiswas');
        $mahasiswas = $jadwalPertemuan->jadwal?->kelasModel?->mahasiswas;

        if (!$mahasiswas) {
            return response()->json(['success' => false, 'message' => 'No students found']);
        }

        if ($validated['status'] === 'null') {
            // Reset all: delete all absensis for this meeting
            $jadwalPertemuan->absensis()->delete();
        } else {
            foreach ($mahasiswas as $mhs) {
                $absensi = \App\Models\Absensi::withTrashed()->updateOrCreate(
                    [
                        'jadwal_pertemuan_id' => $jadwalPertemuan->id,
                        'mahasiswa_id' => $mhs->id
                    ],
                    [
                        'status' => $validated['status'],
                        'jam_masuk' => $validated['status'] == 'hadir' ? now()->format('H:i') : null,
                    ]
                );
                if ($absensi->trashed()) {
                    $absensi->restore();
                }
            }
        }
        return response()->json(['success' => true]);
    }

    /**
     * Attendance recap per class
     */
    public function rekap(Kelas $kelas)
    {
        $kelas->load([
            'mahasiswas',
            'kelasMatakuliahs.mataKuliah',
        ]);

        // Get all meetings for this class
        $jadwals = \App\Models\Jadwal::where('kelas_id', $kelas->id)
            ->with(['mataKuliah', 'pertemuans.absensis'])
            ->get();

        // Build recap data: student x meeting matrix
        $rekapData = $kelas->mahasiswas->map(function ($mhs) use ($jadwals) {
            $pertemuanData = [];
            $totalHadir = 0;
            $totalIzin = 0;
            $totalSakit = 0;
            $totalAlpha = 0;
            $totalPertemuan = 0;

            foreach ($jadwals as $jadwal) {
                foreach ($jadwal->pertemuans as $pertemuan) {
                    $totalPertemuan++;
                    $absensi = $pertemuan->absensis->where('mahasiswa_id', $mhs->id)->first();
                    $status = $absensi?->status ?? 'belum';

                    $pertemuanData[] = [
                        'pertemuan_id' => $pertemuan->id,
                        'mk' => $jadwal->mataKuliah->kode ?? '-',
                        'pertemuan_ke' => $pertemuan->pertemuan_ke,
                        'tanggal' => $pertemuan->tanggal,
                        'status' => $status,
                    ];

                    if ($status === 'hadir')
                        $totalHadir++;
                    if ($status === 'izin')
                        $totalIzin++;
                    if ($status === 'sakit')
                        $totalSakit++;
                    if ($status === 'alpha')
                        $totalAlpha++;
                }
            }

            return [
                'mahasiswa' => [
                    'id' => $mhs->id,
                    'nim' => $mhs->nim,
                    'nama' => $mhs->nama,
                ],
                'pertemuans' => $pertemuanData,
                'summary' => [
                    'hadir' => $totalHadir,
                    'izin' => $totalIzin,
                    'sakit' => $totalSakit,
                    'alpha' => $totalAlpha,
                    'total' => $totalPertemuan,
                    'persen' => $totalPertemuan > 0 ? round(($totalHadir / $totalPertemuan) * 100, 1) : 0,
                ],
            ];
        });

        return Inertia::render('Absensi/Rekap', [
            'kelas' => $kelas,
            'rekapData' => $rekapData,
        ]);
    }
}
