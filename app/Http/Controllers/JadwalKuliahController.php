<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\JadwalPertemuan;
use App\Models\Jadwal;
use Carbon\Carbon;

class JadwalKuliahController extends Controller
{
    /**
     * Jadwal Kuliah page - shows today's schedule and monthly calendar
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Check roles
        $isAkademik = $user->isAkademik();
        $isStaffProdi = $user->isStaffProdi() && !$isAkademik;
        $userProdiIds = $isStaffProdi ? $user->prodis()->pluck('program_studis.id') : [];

        // Check if user is a dosen (not admin/staff)
        $isDosen = $user->isDosen() && !$isAkademik && !$isStaffProdi;
        $dosenId = $isDosen ? $user->dosen?->id : null;

        // Can add schedule = must have jadwal.create permission
        $canAddSchedule = $user->can('jadwal.create');

        // ... [date logic same] ...
        // Get current month for calendar (or from request)
        $calendarMonth = $request->input('month', now()->format('Y-m'));
        $calendarDate = Carbon::parse($calendarMonth . '-01');
        $startOfMonth = $calendarDate->copy()->startOfMonth();
        $endOfMonth = $calendarDate->copy()->endOfMonth();

        // Date range filter (optional, overrides monthly view)
        $filterStart = $request->input('start_date');
        $filterEnd = $request->input('end_date');
        $hasDateFilter = $filterStart && $filterEnd;

        // Base query for meetings
        $baseQuery = JadwalPertemuan::query()
            ->with([
                'jadwal.mataKuliah',
                'jadwal.kelasModel' => function ($q) {
                    $q->withCount('mahasiswas');
                },
                'dosen',
                'ruangan',
            ])
            ->withCount('absensis')
            ->whereHas('jadwal.kelasModel.semester', function ($q) {
                $q->where('is_active', true);
            });

        // Filter by prodi if staff prodi
        if ($isStaffProdi) {
            $baseQuery->whereHas('jadwal.kelasModel', function ($q) use ($userProdiIds) {
                $q->whereIn('prodi_id', $userProdiIds);
            });
        }

        // Filter by dosen if user is a dosen
        if ($dosenId) {
            $baseQuery->where('dosen_id', $dosenId);
        }

        // Today's schedule
        $todaySchedule = (clone $baseQuery)
            ->whereDate('tanggal', now()->toDateString())
            ->orderBy('jadwal_id')
            ->get()
            ->map(function ($meeting) {
                $meeting->has_attendance = $meeting->absensis_count > 0;
                $meeting->attendance_count = $meeting->absensis_count;
                $meeting->student_count = $meeting->jadwal->kelasModel->mahasiswas_count ?? 0;
                return $meeting;
            });

        // Monthly/Filtered schedule for calendar
        $scheduleQuery = clone $baseQuery;
        if ($hasDateFilter) {
            $scheduleQuery->whereBetween('tanggal', [$filterStart, $filterEnd]);
        } else {
            $scheduleQuery->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);
        }

        $monthlySchedule = $scheduleQuery
            ->orderBy('tanggal')
            ->get()
            ->map(function ($meeting) {
                $meeting->has_attendance = $meeting->absensis_count > 0;
                $meeting->attendance_count = $meeting->absensis_count;
                $meeting->student_count = $meeting->jadwal->kelasModel->mahasiswas_count ?? 0;
                return $meeting;
            })
            ->groupBy(function ($m) {
                return Carbon::parse($m->tanggal)->format('Y-m-d');
            });

        // Data for Manual Schedule Modal (only if user can add)
        $availableProdi = [];
        $availableDosens = [];
        $ruangans = [];

        if ($canAddSchedule) {


            // Fetch Prodi with nested Kelas (filtered by active semester)
            $prodiQuery = \App\Models\ProgramStudi::query()
                ->when($isStaffProdi, function ($q) use ($user) {
                    $q->whereIn('id', $user->prodis->pluck('id'));
                })
                ->with([
                    'kelas' => function ($q) {
                        $q->whereHas('semester', fn($s) => $s->where('is_active', true))
                            ->where('status', '!=', 'draft')
                            ->with(['mataKuliahs:id,nama,kode']);
                    }
                ])
                ->get(['id', 'nama', 'kode']);

            $availableProdi = $prodiQuery;

            // Fetch all Dosen for multi-select
            $availableDosens = \App\Models\Dosen::query()
                ->select('id', 'nama', 'nidn')
                ->orderBy('nama')
                ->get();

            $ruangans = \App\Models\Ruangan::all(['id', 'nama']);
        }

        return Inertia::render('JadwalKuliah/Index', [
            'todaySchedule' => $todaySchedule,
            'monthlySchedule' => $monthlySchedule,
            'currentMonth' => $calendarDate->format('Y-m'),
            'isDosen' => $isDosen,
            'dosenName' => $isDosen ? $user->dosen?->nama : null,
            'canAddSchedule' => $canAddSchedule,
            'availableProdi' => $availableProdi,
            'availableDosens' => $availableDosens,
            'ruangans' => $ruangans,
            'filters' => [
                'start_date' => $filterStart,
                'end_date' => $filterEnd,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
            'ruangan_id' => 'nullable|exists:ruangans,id',
            'pertemuan_ke' => 'required|integer',
            'materi' => 'nullable|string',
            'metode_pembelajaran' => 'required|in:online,offline,hybrid',
        ]);

        // Find parent Jadwal (create dummy if not exists - simplified logic: find first matching)
        $jadwal = Jadwal::where('kelas_id', $validated['kelas_id'])
            ->where('mata_kuliah_id', $validated['mata_kuliah_id'])
            ->first();

        if (!$jadwal) {
            // Ideally should fail, but for robustness let's create a placeholder or pick active semester
            // This is a simplification. In real app, we might need to enforce existing Jadwal.
            return back()->withErrors(['mata_kuliah_id' => 'Jadwal master untuk Mata Kuliah ini belum dibuat di kelas tersebut.']);
        }

        try {
            JadwalPertemuan::create([
                'jadwal_id' => $jadwal->id,
                'tanggal' => $validated['tanggal'],
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'ruangan_id' => $validated['ruangan_id'],
                'pertemuan_ke' => $validated['pertemuan_ke'],
                'materi' => $validated['materi'],
                'status' => 'terjadwal',
                'dosen_id' => auth()->user()->isDosen() ? auth()->user()->dosen->id : $jadwal->dosen_id ?? null,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()->withErrors(['pertemuan_ke' => "Pertemuan ke-{$validated['pertemuan_ke']} untuk mata kuliah ini sudah ada. Silakan gunakan nomor pertemuan yang berbeda."]);
        }

        return redirect()->back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Update existing jadwal pertemuan
     */
    public function update(Request $request, \App\Models\JadwalPertemuan $jadwalPertemuan)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
            'mode' => 'required|in:online,offline,hybrid',
            'ruangan_id' => 'nullable|required_if:mode,offline,hybrid|exists:ruangans,id',
            'pertemuan_ke' => 'required|integer',
            'catatan' => 'nullable|string', // Topik/Materi
            'dosen_id' => 'nullable|exists:dosens,id',
        ]);

        $jadwalPertemuan->update([
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'mode' => $validated['mode'],
            // Jika online, set ruangan null, jika tidak pakai yg dikirim
            'ruangan_id' => $validated['mode'] === 'online' ? null : $validated['ruangan_id'],
            'pertemuan_ke' => $validated['pertemuan_ke'],
            'materi' => $validated['catatan'],
            'catatan' => $validated['catatan'], // Sync both fields if schema confusing
            'dosen_id' => $validated['dosen_id'] ?? $jadwalPertemuan->dosen_id,
        ]);

        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui.');
    }
}
