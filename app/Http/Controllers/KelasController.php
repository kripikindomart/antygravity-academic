<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KelasMatakuliah;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\Ruangan;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        // Get active TA and semester
        $activeTa = TahunAkademik::where('is_active', true)->first();
        $activeSemester = $activeTa?->semesters()->where('is_active', true)->first();

        // Get user's assigned prodi (for staff_prodi role)
        $user = auth()->user();
        $userProdiIds = $user->prodis()->pluck('program_studis.id')->toArray();
        $isStaffProdi = $user->hasRole('staff_prodi') && count($userProdiIds) > 0;

        // Check if user is mahasiswa
        $isMahasiswa = $user->hasRole('mahasiswa');
        $mahasiswaProdiId = null;
        if ($isMahasiswa) {
            $mahasiswa = \App\Models\Mahasiswa::where('user_id', $user->id)->first();
            $mahasiswaProdiId = $mahasiswa?->prodi_id;
        }

        // Apply default filters if not set
        $semesterId = $request->semester_id ?? $activeSemester?->id;
        $defaultProdiId = $isMahasiswa ? $mahasiswaProdiId : ($isStaffProdi ? $userProdiIds[0] : null);
        $prodiId = $request->prodi_id ?? $defaultProdiId;

        $query = Kelas::query()
            ->with(['semester.tahunAkademik', 'prodi', 'mataKuliahs'])
            ->withCount(['mataKuliahs', 'mahasiswas'])
            ->when(
                $request->search,
                fn($q, $s) =>
                $q->where('nama', 'like', "%{$s}%")
                    ->orWhere('kode', 'like', "%{$s}%")
            )
            ->when($semesterId, fn($q, $id) => $q->where('semester_id', $id))
            ->when($prodiId, fn($q, $id) => $q->where('prodi_id', $id))
            ->when($isStaffProdi && !$prodiId, fn($q) => $q->whereIn('prodi_id', $userProdiIds))
            ->when($isMahasiswa && $mahasiswaProdiId, fn($q) => $q->where('prodi_id', $mahasiswaProdiId))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest();

        // Get prodis list (limited based on role)
        if ($isMahasiswa && $mahasiswaProdiId) {
            $prodis = ProgramStudi::where('id', $mahasiswaProdiId)->get();
        } elseif ($isStaffProdi) {
            $prodis = ProgramStudi::whereIn('id', $userProdiIds)->orderBy('nama')->get();
        } else {
            $prodis = ProgramStudi::orderBy('nama')->get();
        }

        // Get kurikulums and MKs for modal (create new kelas)
        $kurikulums = \App\Models\Kurikulum::with('prodi')->orderBy('nama')->get();
        $availableMks = MataKuliah::with(['prodi', 'kurikulums'])->orderBy('nama')->get();

        return Inertia::render('Kelas/Index', [
            'kelasList' => $query->paginate(10)->withQueryString(),
            'filters' => [
                'search' => $request->search,
                'semester_id' => $semesterId,
                'prodi_id' => $prodiId,
                'status' => $request->status,
            ],
            'semesters' => Semester::with('tahunAkademik')->orderByDesc('id')->limit(10)->get(),
            'prodis' => $prodis,
            'kurikulums' => $kurikulums,
            'availableMks' => $availableMks,
            'activeSemester' => $activeSemester,
            'activeTa' => $activeTa,
            'userProdiId' => $isStaffProdi ? $userProdiIds[0] : null,
        ]);
    }

    public function create()
    {
        $activeTa = TahunAkademik::where('is_active', true)->first();
        $activeSemester = $activeTa?->semesters()->where('is_active', true)->first();

        return Inertia::render('Kelas/Form', [
            'kelas' => null,
            'semesters' => Semester::with('tahunAkademik')->orderByDesc('id')->limit(10)->get(),
            'prodis' => ProgramStudi::orderBy('nama')->get(),
            'ruangans' => Ruangan::orderBy('nama')->get(),
            'activeSemester' => $activeSemester,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'prodi_id' => 'required|exists:program_studis,id',
            'nama' => 'required|string|max:200',
            'kode' => 'nullable|string|max:50',
            'persen_online' => 'required|integer|min:0|max:100',
            'platform_online' => 'nullable|in:zoom,gmeet,teams',
            'link_online' => 'nullable|string|max:500',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        // Auto-calculate offline percentage
        $validated['persen_offline'] = 100 - $validated['persen_online'];

        $kelas = Kelas::create($validated);

        // Return to index with kelas_id for modal step 2
        return redirect()->route('kelas.index')
            ->with('success', 'Kelas berhasil dibuat')
            ->with('kelas_id', $kelas->id);
    }

    public function edit(Kelas $kelas)
    {
        $kelas->load([
            'semester.tahunAkademik',
            'prodi',
            'ruangans',
            'kelasMatakuliahs.mataKuliah',
            'kelasMatakuliahs.dosens.dosen',
        ]);

        // Get ALL MKs (with kurikulum and prodi for filtering on frontend)
        $allMks = MataKuliah::with(['prodi', 'kurikulums'])
            ->whereNotIn('id', $kelas->mataKuliahs->pluck('id'))
            ->orderBy('nama')
            ->get();

        // Get kurikulums for filter
        $kurikulums = \App\Models\Kurikulum::with('prodi')->orderBy('nama')->get();

        return Inertia::render('Kelas/Form', [
            'kelas' => $kelas,
            'semesters' => Semester::with('tahunAkademik')->orderByDesc('id')->limit(10)->get(),
            'prodis' => ProgramStudi::orderBy('nama')->get(),
            'ruangans' => Ruangan::orderBy('nama')->get(),
            'availableMks' => $allMks,
            'kurikulums' => $kurikulums,
            'dosens' => \App\Models\Dosen::orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:200',
            'kode' => 'nullable|string|max:50',
            'persen_online' => 'required|integer|min:0|max:100',
            'platform_online' => 'nullable|in:zoom,gmeet,teams',
            'link_online' => 'nullable|string|max:500',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'nullable|in:draft,ready,generated',
        ]);

        $validated['persen_offline'] = 100 - $validated['persen_online'];

        $kelas->update($validated);

        return redirect()->back()->with('success', 'Kelas berhasil diperbarui');
    }

    public function destroy(Kelas $kelas)
    {
        $kelas->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus');
    }

    /**
     * Update Kelas status
     */
    public function updateStatus(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,ready,generated',
        ]);

        $kelas->update(['status' => $validated['status']]);

        return back()->with('success', "Status kelas berhasil diubah menjadi {$validated['status']}");
    }

    /**
     * Show Kelas detail page with all data for generate jadwal
     */
    public function show(Kelas $kelas)
    {
        $kelas->load([
            'semester.tahunAkademik',
            'prodi',
            'ruangans',
            'mahasiswas',
            'kelasMatakuliahs.mataKuliah',
            'kelasMatakuliahs.dosens.dosen',
            'kelasMatakuliahs.ruangans', // Preferensi Ruangan
        ]);

        // Get ALL MKs (for enrollment)
        // Only MK not already assigned
        $alreadyAssignedIds = $kelas->kelasMatakuliahs->pluck('mata_kuliah_id');

        $availableMks = MataKuliah::with(['prodi', 'kurikulums'])
            ->whereNotIn('id', $alreadyAssignedIds)
            // Optional: Filter by same Prodi? For now show all.
            ->orderBy('nama')
            ->get();

        // Get Trashed (Soft Deleted) MKs
        $trashedMks = \App\Models\KelasMatakuliah::onlyTrashed()
            ->where('kelas_id', $kelas->id)
            ->with(['mataKuliah', 'dosens.dosen'])
            ->get();

        // Get filter options for Mahasiswa Enrollment
        $filterData = [
            'prodis' => ProgramStudi::orderBy('nama')->get(),
            'angkatans' => \App\Models\Mahasiswa::distinct()->orderByDesc('angkatan')->pluck('angkatan')->filter()->values(),
            'statuses' => ['aktif', 'cuti', 'lulus', 'drop_out'],
        ];

        return Inertia::render('Kelas/Detail', [
            'kelas' => $kelas,
            'availableMks' => $availableMks,
            'kurikulums' => \App\Models\Kurikulum::all(),
            'allRuangans' => Ruangan::orderBy('nama')->get(),
            'dosens' => \App\Models\Dosen::orderBy('nama')->select('id', 'nama', 'nidn', 'nip')->get(),
            'filterData' => $filterData,
            'trashedMks' => $trashedMks,
        ]);
    }

    /**
     * Search available mahasiswa for enrollment (AJAX)
     */
    public function searchCandidates(Request $request, Kelas $kelas)
    {
        $query = \App\Models\Mahasiswa::query()
            ->with(['prodi'])
            ->whereNotIn('id', $kelas->mahasiswas()->pluck('mahasiswas.id'));

        // Search Name/NIM
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nim', 'like', "%{$q}%");
            });
        }

        // Filter Prodi
        if ($request->filled('prodi_id')) {
            $query->where('prodi_id', $request->prodi_id);
        } else {
            // Default: If not admin, force kelas prodi. If admin, user can choose 'All' (empty prodi_id) or specific.
            // But initial load should probably default to Kelas Prodi for relevance.
            // Check entitlement: assume 'admin' role check, fallback to kelas prodi for safety/relevance
            if (!auth()->user()->hasRole(['admin', 'super-admin'])) {
                $query->where('prodi_id', $kelas->prodi_id);
            } else {
                // For admin, if no prodi_id sent, maybe default to kelas prodi?
                // User said "admin, untuk staf prodi default langsung filter".
                // Let's default to kelas prodi if no specific filter requests otherwise, 
                // BUT let allow "all" if specifically requested? 
                // For now, let's default to class prodi if empty, unless a special flag 'all_prodis' is sent?
                // Or just follow the request. If prodi_id is null/empty, show all?
                // Let's filter by Kelas Prodi by default if request is empty, to be safe.
                // The frontend should send the default prodi_id.
            }
        }

        // Filter Angkatan
        if ($request->filled('angkatan')) {
            $query->where('angkatan', $request->angkatan);
        }

        // Filter Semester - Mapping logic if needed. For now assuming skipping complex semester calc
        // or user meant 'Semester Masuk'. Let's skip explicitly unless we calculate it.
        // User asked for "filter semester". Let's assume they want to filter by "mahasiswa semester X".
        // This is hard without global "Current Semester" context.
        // Let's skip semester logic for now or stick to Angkatan which is reliable.

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 20);
        $mahasiswas = $query->orderBy('nama')->paginate($perPage);

        return response()->json($mahasiswas);
    }

    /**
     * Bulk delete kelas
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:kelas,id',
        ]);

        Kelas::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => 'Kelas berhasil dihapus']);
    }

    /**
     * Assign Mata Kuliah to Kelas with jadwal settings
     */
    public function assignMataKuliah(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'hari' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'jam_mulai' => 'nullable',
            'jam_selesai' => 'nullable',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'sesi_per_pertemuan' => 'nullable|integer|min:1|max:4',
            'total_sesi' => 'nullable|integer|min:1|max:32',
        ]);

        // Check if already assigned
        if ($kelas->mataKuliahs()->where('mata_kuliah_id', $validated['mata_kuliah_id'])->exists()) {
            return response()->json(['message' => 'Mata Kuliah sudah ditambahkan'], 422);
        }

        // Use kelas dates as default if not provided
        $kelas->mataKuliahs()->attach($validated['mata_kuliah_id'], [
            'hari' => $validated['hari'] ?? null,
            'jam_mulai' => $validated['jam_mulai'] ?? null,
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'tanggal_mulai' => $validated['tanggal_mulai'] ?? $kelas->tanggal_mulai,
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? $kelas->tanggal_selesai,
            'sesi_per_pertemuan' => $validated['sesi_per_pertemuan'] ?? 2,
            'total_sesi' => $validated['total_sesi'] ?? 16,
        ]);

        return response()->json(['message' => 'Mata Kuliah berhasil ditambahkan']);
    }

    /**
     * Remove Mata Kuliah from Kelas
     */
    public function removeMataKuliah(Kelas $kelas, MataKuliah $mataKuliah)
    {
        $kelas->mataKuliahs()->detach($mataKuliah->id);

        return response()->json(['message' => 'Mata Kuliah berhasil dihapus dari kelas']);
    }

    /**
     * Bulk update settings for multiple MKs in Kelas
     */
    public function bulkUpdateMk(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:kelas_matakuliah,id',
            'hari' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'jam_mulai' => 'nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'jam_selesai' => 'nullable|regex:/^\d{2}:\d{2}(:\d{2})?$/',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'dosen_id' => 'nullable|exists:dosens,id',
            'ruangan_id' => 'nullable|exists:ruangans,id',
            'ruangan_ids' => 'nullable|array',
            'ruangan_ids.*' => 'exists:ruangans,id',
            'total_sesi' => 'nullable|integer|min:1',
            'sesi_per_pertemuan' => 'nullable|integer|min:1',
            'pertemuan_uts' => 'nullable|integer|min:1',
            'pertemuan_uas' => 'nullable|integer|min:1',
        ]);

        $updateData = [];
        if (!empty($validated['hari']))
            $updateData['hari'] = $validated['hari'];
        if (!empty($validated['jam_mulai']))
            $updateData['jam_mulai'] = substr($validated['jam_mulai'], 0, 5); // Strip seconds
        if (!empty($validated['jam_selesai']))
            $updateData['jam_selesai'] = substr($validated['jam_selesai'], 0, 5); // Strip seconds
        if (!empty($validated['tanggal_mulai']))
            $updateData['tanggal_mulai'] = \Carbon\Carbon::parse($validated['tanggal_mulai'])->format('Y-m-d');
        if (!empty($validated['tanggal_selesai']))
            $updateData['tanggal_selesai'] = \Carbon\Carbon::parse($validated['tanggal_selesai'])->format('Y-m-d');
        if (!empty($validated['total_sesi']))
            $updateData['total_sesi'] = $validated['total_sesi'];
        if (!empty($validated['sesi_per_pertemuan']))
            $updateData['sesi_per_pertemuan'] = $validated['sesi_per_pertemuan'];
        if (!empty($validated['pertemuan_uts']))
            $updateData['pertemuan_uts'] = $validated['pertemuan_uts'];
        if (!empty($validated['pertemuan_uas']))
            $updateData['pertemuan_uas'] = $validated['pertemuan_uas'];

        if (!empty($updateData)) {
            KelasMatakuliah::whereIn('id', $validated['ids'])->update($updateData);
        }

        // Handle dosen assignment separately (many-to-many)
        if (!empty($validated['dosen_id'])) {
            foreach ($validated['ids'] as $kelasMkId) {
                $kelasMk = KelasMatakuliah::find($kelasMkId);
                if ($kelasMk && !$kelasMk->dosens()->where('dosen_id', $validated['dosen_id'])->exists()) {
                    $kelasMk->dosens()->create(['dosen_id' => $validated['dosen_id']]);
                }
            }
        }

        // Handle ruangan assignment (bulk add preference)
        if (!empty($validated['ruangan_ids'])) {
            foreach ($validated['ids'] as $kelasMkId) {
                $kelasMk = KelasMatakuliah::find($kelasMkId);
                if ($kelasMk) {
                    $kelasMk->ruangans()->syncWithoutDetaching($validated['ruangan_ids']);
                }
            }
        } elseif (!empty($validated['ruangan_id'])) {
            // Fallback for single legacy input
            foreach ($validated['ids'] as $kelasMkId) {
                $kelasMk = KelasMatakuliah::find($kelasMkId);
                if ($kelasMk) {
                    $kelasMk->ruangans()->syncWithoutDetaching([$validated['ruangan_id']]);
                }
            }
        }

        return response()->json(['message' => 'Settings berhasil diupdate']);
    }

    /**
     * Bulk remove MKs from Kelas
     */
    public function bulkRemoveMk(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:kelas_matakuliah,id',
        ]);

        KelasMatakuliah::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => 'Mata Kuliah berhasil dihapus']);
    }

    /**
     * Update jadwal for MK in Kelas
     */
    public function updateMataKuliahJadwal(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $validated = $request->validate([
            'hari' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'sesi_per_pertemuan' => 'nullable|integer|min:1|max:4',
            'total_sesi' => 'nullable|integer|min:1|max:32',
        ]);

        $kelasMatakuliah->update($validated);

        return response()->json(['message' => 'Jadwal berhasil diperbarui']);
    }

    /**
     * Assign Dosen to MK in Kelas
     */
    public function assignDosen(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $validated = $request->validate([
            'dosen_id' => 'required|exists:dosens,id',
            'is_koordinator' => 'nullable|boolean',
            'sesi_mulai' => 'nullable|integer|min:1',
            'sesi_selesai' => 'nullable|integer|min:1',
        ]);

        // Check if already assigned
        if ($kelasMatakuliah->dosens()->where('dosen_id', $validated['dosen_id'])->exists()) {
            return response()->json(['message' => 'Dosen sudah ditambahkan'], 422);
        }

        $kelasMatakuliah->dosens()->create($validated);

        return response()->json(['message' => 'Dosen berhasil ditambahkan']);
    }

    public function updateDosenSesi(Request $request)
    {
        $validated = $request->validate([
            'kelas_matakuliah_id' => 'required|exists:kelas_matakuliah,id',
            'dosen_id' => 'required|exists:dosens,id',
            'sesi_mulai' => 'nullable|integer',
            'sesi_selesai' => 'nullable|integer',
        ]);

        $pivot = \App\Models\KelasMkDosen::where('kelas_matakuliah_id', $validated['kelas_matakuliah_id'])
            ->where('dosen_id', $validated['dosen_id'])
            ->firstOrFail();

        $update = [];
        if (array_key_exists('sesi_mulai', $validated))
            $update['sesi_mulai'] = $validated['sesi_mulai'];
        if (array_key_exists('sesi_selesai', $validated))
            $update['sesi_selesai'] = $validated['sesi_selesai'];

        if (!empty($update))
            $pivot->update($update);

        return response()->json(['message' => 'Sesi dosen update']);
    }

    /**
     * Remove Dosen from MK
     */
    /**
     * Remove Dosen from MK
     */
    public function removeDosen(KelasMatakuliah $kelasMatakuliah, $dosenId)
    {
        $kelasMatakuliah->dosens()->where('dosen_id', $dosenId)->delete();

        return response()->json(['message' => 'Dosen berhasil dihapus']);
    }

    /**
     * Add Ruangan to MK (Preference)
     */
    public function addRuangan(Request $request, KelasMatakuliah $kelasMatakuliah)
    {
        $validated = $request->validate([
            'ruangan_id' => 'required|exists:ruangans,id',
        ]);

        // Check if already assigned
        if (!$kelasMatakuliah->ruangans()->where('ruangan_id', $validated['ruangan_id'])->exists()) {
            $kelasMatakuliah->ruangans()->attach($validated['ruangan_id']);
        }

        return response()->json(['message' => 'Ruangan berhasil ditambahkan']);
    }

    /**
     * Remove Ruangan from MK (Preference)
     */
    public function removeRuangan(KelasMatakuliah $kelasMatakuliah, $ruanganId)
    {
        $kelasMatakuliah->ruangans()->detach($ruanganId);

        return response()->json(['message' => 'Ruangan berhasil dihapus']);
    }

    /**
     * Sync Ruangan for Kelas
     */
    public function syncRuangan(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'ruangan_ids' => 'array',
            'ruangan_ids.*' => 'exists:ruangans,id',
        ]);

        // Create sync array with prioritas
        $syncData = [];
        foreach ($validated['ruangan_ids'] ?? [] as $index => $ruanganId) {
            $syncData[$ruanganId] = ['prioritas' => $index + 1];
        }

        $kelas->ruangans()->sync($syncData);

        return response()->json(['message' => 'Ruangan berhasil diperbarui']);
    }

    /**
     * Bulk enroll mahasiswa to Kelas
     */
    public function bulkEnrollMahasiswa(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'mahasiswa_ids' => 'required|array',
            'mahasiswa_ids.*' => 'exists:mahasiswas,id',
        ]);

        // Filter: only enroll mahasiswa not already enrolled
        $existingIds = $kelas->mahasiswas->pluck('id')->toArray();
        $newIds = array_diff($validated['mahasiswa_ids'], $existingIds);

        if (count($newIds) > 0) {
            $attachData = [];
            foreach ($newIds as $id) {
                $attachData[$id] = ['status' => 'aktif'];
            }
            $kelas->mahasiswas()->attach($attachData);
        }

        return response()->json([
            'message' => count($newIds) . ' mahasiswa berhasil ditambahkan ke kelas'
        ]);
    }

    /**
     * Remove mahasiswa from Kelas
     */
    public function removeMahasiswa(Kelas $kelas, $mahasiswaId)
    {
        $kelas->mahasiswas()->detach($mahasiswaId);

        return response()->json(['message' => 'Mahasiswa berhasil dihapus dari kelas']);
    }

    /**
     * Bulk remove mahasiswa from Kelas
     */
    public function bulkRemoveMahasiswa(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'mahasiswa_ids' => 'required|array',
            'mahasiswa_ids.*' => 'exists:mahasiswas,id',
        ]);

        $kelas->mahasiswas()->detach($validated['mahasiswa_ids']);

        return response()->json([
            'message' => count($validated['mahasiswa_ids']) . ' mahasiswa berhasil dihapus dari kelas'
        ]);
    }
    /**
     * Generate Jadwal Otomatis dengan Smart Conflict Detection
     * 
     * 3 Jenis Bentrok:
     * 1. Ruangan Bentrok (Global) → Auto switch ke Online
     * 2. Dosen Bentrok (Global) → Coba team teaching, jika tidak → Warning
     * 3. Kelas Bentrok (Within Same Class) → MK berbeda di jam sama → Warning
     */
    public function generateJadwal(Kelas $kelas)
    {
        // Load Dependencies
        $kelas->load(['semester.tahunAkademik', 'kelasMatakuliahs.dosens.dosen', 'kelasMatakuliahs.ruangans', 'kelasMatakuliahs.mataKuliah']);

        $tahunAkademik = $kelas->semester->tahunAkademik;
        if (!$tahunAkademik || !$tahunAkademik->tanggal_mulai) {
            return response()->json(['message' => 'Kalender Akademik tidak valid (Tanggal Mulai belum set)'], 422);
        }

        $startDate = \Carbon\Carbon::parse($tahunAkademik->tanggal_mulai);
        $persenOnline = $kelas->persen_online ?? 0;
        $kelasId = $kelas->id;

        // ========== PHASE 1: COLLECT PROPOSALS ==========
        $proposals = [];
        $jadwalHeaders = [];

        foreach ($kelas->kelasMatakuliahs as $km) {
            if (!$km->hari || !$km->jam_mulai)
                continue;

            // Use custom tanggal_mulai if set, otherwise fallback to tahunAkademik
            $mkStartDate = $km->tanggal_mulai
                ? \Carbon\Carbon::parse($km->tanggal_mulai)
                : $startDate->copy();

            $currentDate = $mkStartDate->copy();
            $hariMap = [
                'senin' => \Carbon\Carbon::MONDAY,
                'selasa' => \Carbon\Carbon::TUESDAY,
                'rabu' => \Carbon\Carbon::WEDNESDAY,
                'kamis' => \Carbon\Carbon::THURSDAY,
                'jumat' => \Carbon\Carbon::FRIDAY,
                'sabtu' => \Carbon\Carbon::SATURDAY,
                'minggu' => \Carbon\Carbon::SUNDAY,
            ];
            $targetDay = $hariMap[strtolower($km->hari)] ?? null;
            if (!$targetDay)
                continue;

            if ($currentDate->dayOfWeekIso !== $targetDay) {
                $currentDate->next($targetDay);
            }

            $totalSesi = $km->total_sesi ?? 16;
            $sesiPerPertemuan = $km->sesi_per_pertemuan ?? 1;
            $totalPertemuan = (int) ceil($totalSesi / $sesiPerPertemuan);
            $onlineMeetings = (int) round($totalPertemuan * $persenOnline / 100);

            // Randomized online indices distribution (not template-like)
            $onlineIndices = [];
            if ($onlineMeetings > 0 && $totalPertemuan > 0) {
                $allIndices = range(1, $totalPertemuan);
                shuffle($allIndices);
                $onlineIndices = array_slice($allIndices, 0, $onlineMeetings);
            }

            $currentSesi = 1;
            $mingguKe = 1;
            $jamMulai = substr($km->jam_mulai, 0, 5);
            $jamSelesai = substr($km->jam_selesai, 0, 5);

            $jadwalHeaders[$km->id] = [
                'km' => $km,
                'hari' => $km->hari,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'mk_id' => $km->mata_kuliah_id,
            ];

            while ($currentSesi <= $totalSesi) {
                $rangeEnd = min($currentSesi + $sesiPerPertemuan - 1, $totalSesi);

                $tipe = 'kuliah';
                $utsSesi = $km->pertemuan_uts ?? 8;
                $uasSesi = $km->pertemuan_uas ?? 16;

                if ($currentSesi <= $utsSesi && $rangeEnd >= $utsSesi)
                    $tipe = 'uts';
                elseif ($currentSesi <= $uasSesi && $rangeEnd >= $uasSesi)
                    $tipe = 'uas';

                // Determine Primary Dosen
                $dosenId = null;
                $allDosens = $km->dosens->toArray();
                foreach ($km->dosens as $dosenPivot) {
                    $start = $dosenPivot->sesi_mulai ?? 1;
                    $end = $dosenPivot->sesi_selesai ?? $totalSesi;
                    if ($currentSesi >= $start && $currentSesi <= $end) {
                        $dosenId = $dosenPivot->dosen_id;
                        break;
                    }
                }
                if (!$dosenId && $km->dosens->count() > 0) {
                    $dosenId = $km->dosens->first()->dosen_id;
                }

                $mode = in_array($mingguKe, $onlineIndices) ? 'online' : 'offline';

                // Rotate ruangan selection (use different ruangan for each pertemuan)
                $ruanganId = null;
                if ($mode === 'offline' && $km->ruangans->count() > 0) {
                    $ruanganIndex = ($mingguKe - 1) % $km->ruangans->count();
                    $ruanganId = $km->ruangans->values()[$ruanganIndex]->id;
                }

                $proposals[] = [
                    'km_id' => $km->id,
                    'mk_id' => $km->mata_kuliah_id,
                    'mk_nama' => $km->mataKuliah->nama ?? '-',
                    'pertemuan_ke' => $mingguKe,
                    'tanggal' => $currentDate->format('Y-m-d'),
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'sesi_mulai' => $currentSesi,
                    'sesi_selesai' => $rangeEnd,
                    'tipe' => $tipe,
                    'dosen_id' => $dosenId,
                    'ruangan_id' => $ruanganId,
                    'mode' => $mode,
                    'all_dosens' => $allDosens,
                    'all_ruangans' => $km->ruangans->pluck('id')->toArray(),
                    'conflicts' => [],
                    'resolved' => true,
                ];

                $currentSesi = $rangeEnd + 1;
                $mingguKe++;
                $currentDate->addWeek();
            }
        }

        if (empty($proposals)) {
            return response()->json(['message' => 'Tidak ada MK dengan jadwal valid untuk digenerate'], 422);
        }

        // ========== PHASE 2: CHECK & RESOLVE CONFLICTS ==========
        $conflictSummary = ['ruangan' => 0, 'dosen' => 0, 'kelas' => 0, 'resolved' => 0, 'unresolved' => 0];

        foreach ($proposals as &$p) {
            $conflictMessages = [];

            // === 1. RUANGAN BENTROK (Global - any class) ===
            if ($p['ruangan_id'] && $p['mode'] === 'offline') {
                $ruanganConflict = \App\Models\JadwalPertemuan::where('tanggal', $p['tanggal'])
                    ->where('ruangan_id', $p['ruangan_id'])
                    ->whereHas('jadwal', function ($q) use ($p, $kelasId) {
                        $q->where('kelas_id', '!=', $kelasId)
                            ->where('jam_mulai', '<', $p['jam_selesai'])
                            ->where('jam_selesai', '>', $p['jam_mulai']);
                    })
                    ->exists();

                if ($ruanganConflict) {
                    $conflictSummary['ruangan']++;
                    $resolved = false;

                    // Get conflict details for the message
                    $conflictDetail = \App\Models\JadwalPertemuan::where('tanggal', $p['tanggal'])
                        ->where('ruangan_id', $p['ruangan_id'])
                        ->whereHas('jadwal', function ($q) use ($p, $kelasId) {
                            $q->where('kelas_id', '!=', $kelasId)
                                ->where('jam_mulai', '<', $p['jam_selesai'])
                                ->where('jam_selesai', '>', $p['jam_mulai']);
                        })
                        ->with('jadwal.mataKuliah', 'jadwal.kelas')
                        ->first();
                    $conflictWith = $conflictDetail ? ($conflictDetail->jadwal?->kelas?->nama ?? 'Kelas Lain') . ' - ' . ($conflictDetail->jadwal?->mataKuliah?->nama ?? 'MK') : 'kelas lain';

                    // Try alternative ruangans from the MK's ruangan list
                    foreach ($p['all_ruangans'] ?? [] as $altRuanganId) {
                        if ($altRuanganId == $p['ruangan_id'])
                            continue;

                        $altConflict = \App\Models\JadwalPertemuan::where('tanggal', $p['tanggal'])
                            ->where('ruangan_id', $altRuanganId)
                            ->whereHas('jadwal', function ($q) use ($p, $kelasId) {
                                $q->where('kelas_id', '!=', $kelasId)
                                    ->where('jam_mulai', '<', $p['jam_selesai'])
                                    ->where('jam_selesai', '>', $p['jam_mulai']);
                            })
                            ->exists();

                        if (!$altConflict) {
                            $p['ruangan_id'] = $altRuanganId;
                            $conflictMessages[] = "Ruangan bentrok dengan {$conflictWith} → Pindah ke ruangan lain";
                            $conflictSummary['resolved']++;
                            $resolved = true;
                            break;
                        }
                    }

                    // Only switch to online if ALL ruangans are conflicted
                    if (!$resolved) {
                        $p['mode'] = 'online';
                        $p['ruangan_id'] = null;
                        $conflictMessages[] = "Semua ruangan bentrok dengan {$conflictWith} → Auto Online";
                        $conflictSummary['resolved']++;
                    }
                }
            }

            // === 2. DOSEN BENTROK (Global - any class) ===
            if ($p['dosen_id']) {
                $dosenConflict = \App\Models\JadwalPertemuan::where('tanggal', $p['tanggal'])
                    ->where('dosen_id', $p['dosen_id'])
                    ->whereHas('jadwal', function ($q) use ($p, $kelasId) {
                        $q->where('kelas_id', '!=', $kelasId)
                            ->where('jam_mulai', '<', $p['jam_selesai'])
                            ->where('jam_selesai', '>', $p['jam_mulai']);
                    })
                    ->with('jadwal.mataKuliah', 'jadwal.kelas')
                    ->first();

                if ($dosenConflict) {
                    $conflictSummary['dosen']++;
                    $resolved = false;

                    // Try team teaching swap
                    if (count($p['all_dosens']) > 1) {
                        foreach ($p['all_dosens'] as $altDosen) {
                            if ($altDosen['dosen_id'] == $p['dosen_id'])
                                continue;

                            $stillConflict = \App\Models\JadwalPertemuan::where('tanggal', $p['tanggal'])
                                ->where('dosen_id', $altDosen['dosen_id'])
                                ->whereHas('jadwal', function ($q) use ($p, $kelasId) {
                                    $q->where('kelas_id', '!=', $kelasId)
                                        ->where('jam_mulai', '<', $p['jam_selesai'])
                                        ->where('jam_selesai', '>', $p['jam_mulai']);
                                })->exists();

                            if (!$stillConflict) {
                                $p['dosen_id'] = $altDosen['dosen_id'];
                                $conflictMessages[] = 'Dosen bentrok → Swap team teaching';
                                $conflictSummary['resolved']++;
                                $resolved = true;
                                break;
                            }
                        }
                    }

                    if (!$resolved) {
                        $conflictWith = ($dosenConflict->jadwal?->kelas?->nama ?? 'Kelas Lain') . ' - ' . ($dosenConflict->jadwal?->mataKuliah?->nama ?? 'MK');
                        $conflictMessages[] = "⚠️ Dosen bentrok: {$conflictWith}";
                        $p['resolved'] = false;
                        $conflictSummary['unresolved']++;
                    }
                }
            }

            // === 3. KELAS BENTROK (Within Same Class - different MK at same time) ===
            $kelasConflict = \App\Models\JadwalPertemuan::where('tanggal', $p['tanggal'])
                ->whereHas('jadwal', function ($q) use ($p, $kelasId) {
                    $q->where('kelas_id', $kelasId)  // SAME class
                        ->where('mata_kuliah_id', '!=', $p['mk_id'])  // Different MK
                        ->where('jam_mulai', '<', $p['jam_selesai'])
                        ->where('jam_selesai', '>', $p['jam_mulai']);
                })
                ->with('jadwal.mataKuliah')
                ->first();

            if ($kelasConflict) {
                $conflictSummary['kelas']++;
                $conflictWith = $kelasConflict->jadwal?->mataKuliah?->nama ?? 'MK Lain';
                $conflictMessages[] = "⚠️ Kelas bentrok dengan MK: {$conflictWith}";
                $p['resolved'] = false;
                $conflictSummary['unresolved']++;
            }

            $p['conflicts'] = $conflictMessages;
        }
        unset($p);

        // ========== PHASE 3: CREATE ==========
        $countGenerated = 0;

        foreach ($jadwalHeaders as $kmId => $header) {
            $km = $header['km'];

            $jadwal = \App\Models\Jadwal::firstOrCreate(
                [
                    'kelas_id' => $kelas->id,
                    'mata_kuliah_id' => $km->mata_kuliah_id,
                    'hari' => $header['hari'],
                    'jam_mulai' => $header['jam_mulai'],
                ],
                [
                    'jam_selesai' => $header['jam_selesai'],
                    'ruangan_id' => $km->ruangans->first()?->id,
                    'semester_id' => $kelas->semester_id,
                ]
            );

            // Delete old pertemuans
            $jadwal->pertemuans()->forceDelete();

            // Create new
            $kmProposals = array_filter($proposals, fn($p) => $p['km_id'] == $kmId);
            foreach ($kmProposals as $p) {
                $catatan = null;
                if (!empty($p['conflicts'])) {
                    $catatan = implode(' | ', $p['conflicts']);
                }

                \App\Models\JadwalPertemuan::create([
                    'jadwal_id' => $jadwal->id,
                    'pertemuan_ke' => $p['pertemuan_ke'],
                    'tanggal' => $p['tanggal'],
                    'sesi_mulai' => $p['sesi_mulai'],
                    'sesi_selesai' => $p['sesi_selesai'],
                    'tipe' => $p['tipe'],
                    'dosen_id' => $p['dosen_id'],
                    'ruangan_id' => $p['ruangan_id'],
                    'mode' => $p['mode'],
                    'status' => 'terjadwal',
                    'catatan' => $catatan,
                ]);
                $countGenerated++;
            }
        }

        return response()->json([
            'message' => "Jadwal berhasil digenerate ({$countGenerated} pertemuan)",
            'generated' => $countGenerated,
            'summary' => $conflictSummary,
            'has_unresolved' => $conflictSummary['unresolved'] > 0,
        ]);
    }

    /**
     * Check if Ruangan is booked at specific time (GLOBAL - across all Kelas/Prodi)
     */
    private function checkGlobalRuanganConflict($tanggal, $jamMulai, $jamSelesai, $ruanganId)
    {
        return \App\Models\JadwalPertemuan::where('tanggal', $tanggal)
            ->where('ruangan_id', $ruanganId)
            ->whereHas('jadwal', function ($q) use ($jamMulai, $jamSelesai) {
                $q->where('jam_mulai', '<', $jamSelesai)
                    ->where('jam_selesai', '>', $jamMulai);
            })
            ->with('jadwal.mataKuliah')
            ->first();
    }

    /**
     * Check if Dosen is booked at specific time (GLOBAL)
     */
    private function checkGlobalDosenConflict($tanggal, $jamMulai, $jamSelesai, $dosenId)
    {
        $conflict = \App\Models\JadwalPertemuan::where('tanggal', $tanggal)
            ->where('dosen_id', $dosenId)
            ->whereHas('jadwal', function ($q) use ($jamMulai, $jamSelesai) {
                $q->where('jam_mulai', '<', $jamSelesai)
                    ->where('jam_selesai', '>', $jamMulai);
            })
            ->with('jadwal.mataKuliah', 'jadwal.kelas')
            ->first();

        if ($conflict) {
            return [
                'mk' => $conflict->jadwal->mataKuliah->nama ?? '-',
                'kelas' => $conflict->jadwal->kelas->nama ?? '-',
                'jam' => $conflict->jadwal->jam_mulai . '-' . $conflict->jadwal->jam_selesai,
            ];
        }
        return null;
    }

    /**
     * Find any free ruangan at specific time
     */
    private function findFreeRuangan($tanggal, $jamMulai, $jamSelesai)
    {
        $bookedRuanganIds = \App\Models\JadwalPertemuan::where('tanggal', $tanggal)
            ->whereHas('jadwal', function ($q) use ($jamMulai, $jamSelesai) {
                $q->where('jam_mulai', '<', $jamSelesai)
                    ->where('jam_selesai', '>', $jamMulai);
            })
            ->whereNotNull('ruangan_id')
            ->pluck('ruangan_id')
            ->toArray();

        return Ruangan::whereNotIn('id', $bookedRuanganIds)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Reset/Delete All Jadwal for Kelas
     */
    public function resetJadwal(Kelas $kelas)
    {
        // Use database transaction
        DB::transaction(function () use ($kelas) {
            // Get all jadwals
            $jadwals = \App\Models\Jadwal::where('kelas_id', $kelas->id)->get();
            foreach ($jadwals as $jadwal) {
                $jadwal->pertemuans()->forceDelete();
                $jadwal->delete(); // Soft delete header or force? Let's use delete() which is soft if trait exists
            }
        });

        return back()->with('success', 'Semua jadwal berhasil dihapus');
    }

    /**
     * Jadwal Matrix View - Grid view like draft jadwal
     * Tabel 1: MK semester dates, Tabel 2: MK custom tanggal_mulai dates
     */
    public function jadwalMatrix(Kelas $kelas)
    {
        $kelas->load([
            'semester.tahunAkademik',
            'prodi',
            'kelasMatakuliahs.mataKuliah',
            'kelasMatakuliahs.dosens.dosen'
        ]);
        $kelas->loadCount('mahasiswas');

        // Get all jadwal pertemuans for this kelas
        $jadwals = \App\Models\Jadwal::where('kelas_id', $kelas->id)
            ->with(['mataKuliah', 'pertemuans.dosen', 'pertemuans.ruangan'])
            ->get();

        // Group 1: Default (semester start date)
        $defaultGroup = ['dates' => [], 'mks' => []];
        // Group 2: Custom (custom tanggal_mulai)
        $customGroup = ['dates' => [], 'mks' => []];
        $conflicts = [];

        foreach ($jadwals as $jadwal) {
            $mk = $jadwal->mataKuliah;
            if (!$mk)
                continue; // Skip if MK is missing

            $mkId = $jadwal->mata_kuliah_id;
            $mkCode = $jadwal->mataKuliah->kode ?? '-';
            $mkName = $jadwal->mataKuliah->nama ?? '-';
            $sks = $jadwal->mataKuliah->sks ?? 0;
            $waktu = ucfirst(strtolower($jadwal->hari)) . ' ' . substr($jadwal->jam_mulai, 0, 5) . '-' . substr($jadwal->jam_selesai, 0, 5);

            // Get kelasMatakuliah to check for custom matrix group
            $kelasMk = $kelas->kelasMatakuliahs->where('mata_kuliah_id', $mkId)->first();

            // Format dosen names with gelar, separated by
            $dosenRef = $kelasMk
                ? $kelasMk->dosens->map(fn($d) => $this->formatNamaDosen($d->dosen))->join(' / ')
                : '-';

            $pertemuans = $jadwal->pertemuans;

            // Determine Group based on matrix_group column (1=Default, 2=Custom)
            $matrixGroup = $kelasMk?->matrix_group ?? 1;

            // Backup logic: if matrix_group is default (1) but date is significantly different, suggest it might be custom (optional, but let's stick to explicit user setting now)
            // But for initial view (if matrix_group is 1 for all), let's keep the auto-detection ONLY if we want to migrate logic.
            // The user wanted Manual Control. So we rely on matrix_group.
            // HOWEVER, since we just added the column, all are 1. We might want to auto-set 2 if it's far apart?
            // Let's rely on the user setting it manually from now on, defaulting to 1.
            $isCustomGroup = $matrixGroup == 2;

            $mkData = [
                'id' => $mkId,
                'kode' => $mk->kode,
                'nama' => $mkName,
                'sks' => $mk->total_sks ?? ($mk->sks_teori + $mk->sks_praktik ?? 0),
                'waktu' => $waktu,
                'dosens' => $dosenRef ?: '-',
                'jml_mhs' => $kelasMk?->jumlah_mahasiswa ?? 16,
                'dates' => [],
                'matrix_group' => $matrixGroup, // Pass for UI
            ];

            foreach ($pertemuans as $pertemuan) {
                if (!$pertemuan->tanggal)
                    continue;

                $dateStr = $pertemuan->tanggal instanceof \Carbon\Carbon
                    ? $pertemuan->tanggal->format('Y-m-d')
                    : (string) $pertemuan->tanggal;

                // Add to group's dates
                if ($isCustomGroup) {
                    $customGroup['dates'][$dateStr] = true;
                } else {
                    $defaultGroup['dates'][$dateStr] = true;
                }

                $dosenName = $pertemuan->dosen ? $this->formatNamaDosen($pertemuan->dosen) : '';

                // Initials Logic: UTS/UAS > Custom > Default
                if (strtoupper($pertemuan->tipe) === 'UTS' || strtoupper($pertemuan->tipe) === 'UAS') {
                    $initials = strtoupper($pertemuan->tipe);
                } elseif ($kelasMk?->custom_initials) {
                    $initials = $kelasMk->custom_initials;
                } else {
                    $initials = $this->getInitials($dosenName);
                }

                $hasConflict = $this->checkPertemuanConflict($pertemuan, $kelas->id);
                // Color Logic: Custom > Default
                $bgColor = $kelasMk?->custom_color ?? null;

                $mkData['dates'][$dateStr] = [
                    'mode' => $pertemuan->mode,
                    'initials' => $initials,
                    'dosen' => $dosenName,
                    'ruangan' => $pertemuan->ruangan?->nama ?? '-',
                    'pertemuan_ke' => $pertemuan->pertemuan_ke,
                    'tipe' => $pertemuan->tipe,
                    'has_conflict' => $hasConflict,
                    'custom_color' => $bgColor,
                ];

                if ($hasConflict) {
                    $conflicts[] = [
                        'mk' => $mkName,
                        'tanggal' => $dateStr,
                        'pertemuan_ke' => $pertemuan->pertemuan_ke,
                    ];
                }
            }

            if ($isCustomGroup) {
                $customGroup['mks'][] = $mkData;
            } else {
                $defaultGroup['mks'][] = $mkData;
            }
        }

        // Sort dates
        $defaultDates = array_keys($defaultGroup['dates']);
        sort($defaultDates);
        $customDates = array_keys($customGroup['dates']);
        sort($customDates);

        return Inertia::render('Kelas/JadwalMatrix', [
            'kelas' => $kelas,
            'defaultGroup' => [
                'dates' => $defaultDates,
                'mks' => $defaultGroup['mks'],
            ],
            'customGroup' => [
                'dates' => $customDates,
                'mks' => $customGroup['mks'],
            ],
            'conflicts' => $conflicts,
            // Pass all MKs for settings modal
            'allMks' => $kelas->kelasMatakuliahs->map(function ($km) {
                return [
                    'id' => $km->mata_kuliah_id,
                    'kode' => $km->mataKuliah->kode ?? '-',
                    'nama' => $km->mataKuliah->nama ?? '-',
                    'sks' => $km->mataKuliah->total_sks ?? ($km->mataKuliah->sks_teori + $km->mataKuliah->sks_praktik ?? 0),
                    'matrix_group' => $km->matrix_group ?? 1,
                    'initials' => $km->custom_initials ?? '',
                    'color' => $km->custom_color ?? '',
                ];
            })->values(),
        ]);
    }

    /**
     * Update Matrix Settings
     */
    public function updateMatrixSettings(Request $request, Kelas $kelas)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $setting) {
            \Illuminate\Support\Facades\DB::table('kelas_matakuliah')
                ->where('kelas_id', $kelas->id)
                ->where('mata_kuliah_id', $setting['id'])
                ->update([
                    'matrix_group' => $setting['group'],
                    'custom_initials' => $setting['initials'] ?? null,
                    'custom_color' => $setting['color'] ?? null,
                ]);
        }

        return redirect()->back()->with('success', 'Pengaturan Matrix berhasil disimpan.');
    }

    /**
     * Get initials from name
     */
    private function getInitials(string $name): string
    {
        if (empty($name))
            return '-';

        // Remove titles
        $name = preg_replace('/^(Prof\.|Dr\.|Drs\.|M\.Pd\.|M\.Si\.|S\.Pd\.|H\.|Hj\.)\s*/i', '', $name);
        $name = preg_replace('/,\s*(M\.Pd|M\.Si|Ph\.D|S\.Pd|S\.Kom)\.?$/i', '', $name);

        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        return substr($initials, 0, 3); // Max 3 chars
    }

    /**
     * Check if a pertemuan has conflict
     */
    private function checkPertemuanConflict($pertemuan, $kelasId): bool
    {
        if (!$pertemuan->tanggal)
            return false;

        $jadwal = $pertemuan->jadwal;
        if (!$jadwal)
            return false;

        $jamMulai = substr($jadwal->jam_mulai ?? '00:00', 0, 5);
        $jamSelesai = substr($jadwal->jam_selesai ?? '23:59', 0, 5);

        // Check Kelas conflict (same class, different MK)
        $kelasConflict = \App\Models\JadwalPertemuan::where('tanggal', $pertemuan->tanggal)
            ->where('id', '!=', $pertemuan->id)
            ->whereHas('jadwal', function ($q) use ($kelasId, $jadwal, $jamMulai, $jamSelesai) {
                $q->where('kelas_id', $kelasId)
                    ->where('mata_kuliah_id', '!=', $jadwal->mata_kuliah_id)
                    ->where('jam_mulai', '<', $jamSelesai)
                    ->where('jam_selesai', '>', $jamMulai);
            })
            ->exists();

        if ($kelasConflict)
            return true;

        // Check Dosen conflict
        if ($pertemuan->dosen_id) {
            $dosenConflict = \App\Models\JadwalPertemuan::where('tanggal', $pertemuan->tanggal)
                ->where('dosen_id', $pertemuan->dosen_id)
                ->where('id', '!=', $pertemuan->id)
                ->whereHas('jadwal', function ($q) use ($kelasId, $jamMulai, $jamSelesai) {
                    $q->where('kelas_id', '!=', $kelasId)
                        ->where('jam_mulai', '<', $jamSelesai)
                        ->where('jam_selesai', '>', $jamMulai);
                })
                ->exists();
            if ($dosenConflict)
                return true;
        }

        // Check Ruangan conflict
        if ($pertemuan->ruangan_id && $pertemuan->mode !== 'online') {
            $ruanganConflict = \App\Models\JadwalPertemuan::where('tanggal', $pertemuan->tanggal)
                ->where('ruangan_id', $pertemuan->ruangan_id)
                ->where('id', '!=', $pertemuan->id)
                ->whereHas('jadwal', function ($q) use ($kelasId, $jamMulai, $jamSelesai) {
                    $q->where('kelas_id', '!=', $kelasId)
                        ->where('jam_mulai', '<', $jamSelesai)
                        ->where('jam_selesai', '>', $jamMulai);
                })
                ->exists();
            if ($ruanganConflict)
                return true;
        }

        return false;
    }

    /**
     * Store Manual Jadwal (Pertemuan)
     */
    public function storeManualJadwal(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'kelas_matakuliah_id' => 'required',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'ruangan_id' => 'nullable|exists:ruangans,id',
            'dosen_id' => 'nullable|exists:dosens,id',
            'materi' => 'nullable|string',
            'mode' => 'nullable|in:online,offline,hybrid'
        ]);

        $kelasMk = \App\Models\KelasMatakuliah::findOrFail($validated['kelas_matakuliah_id']);

        // Find existing Parent Jadwal or Create one
        $jadwal = \App\Models\Jadwal::where('kelas_id', $kelas->id)
            ->where('mata_kuliah_id', $kelasMk->mata_kuliah_id)
            ->first();

        // If no jadwal exists (e.g. purely manual), create a placeholder
        if (!$jadwal) {
            $jadwal = \App\Models\Jadwal::create([
                'kelas_id' => $kelas->id,
                'semester_id' => $kelas->semester_id,
                'mata_kuliah_id' => $kelasMk->mata_kuliah_id,
                'hari' => null, // Manual
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'ruangan_id' => $validated['ruangan_id'] // Optional default
            ]);
        }

        $lastPertemuan = \App\Models\JadwalPertemuan::whereHas('jadwal', function ($q) use ($kelas, $kelasMk) {
            $q->where('kelas_id', $kelas->id)
                ->where('mata_kuliah_id', $kelasMk->mata_kuliah_id);
        })
            ->max('pertemuan_ke');

        $pertemuanKe = $request->input('pertemuan_ke') ?? (($lastPertemuan ?? 0) + 1);

        // Validation Check for Duplicate Pertemuan
        if ($jadwal->pertemuans()->where('pertemuan_ke', $pertemuanKe)->exists()) {
            return back()->withErrors(['pertemuan_ke' => "Pertemuan ke-$pertemuanKe sudah ada di jadwal ini. Harap gunakan pertemuan lain."]);
        }

        // Determine Mode: Input > Room Based > Default Offline
        $mode = $validated['mode'] ?? ($validated['ruangan_id'] ? 'offline' : 'online');

        // Create Pertemuan
        $pertemuan = $jadwal->pertemuans()->create([
            'tanggal' => $validated['tanggal'],
            'pertemuan_ke' => $pertemuanKe,
            'dosen_id' => $validated['dosen_id'],
            'ruangan_id' => $validated['ruangan_id'],
            'status' => 'Terjadwal',
            'mode' => $mode,
        ]);

        // If materi provided, create Jurnal
        if (!empty($validated['materi'])) {
            $pertemuan->jurnal()->create([
                'materi' => $validated['materi'],
                'status' => 'Menunggu Verifikasi'
            ]);
        }

        return redirect()->back()->with('success', 'Jadwal manual berhasil ditambahkan');
    }

    /**
     * Restore Soft Deleted KelasMatakuliah
     */
    public function restoreMatakuliah($id)
    {
        $km = \App\Models\KelasMatakuliah::onlyTrashed()->findOrFail($id);
        $km->restore();

        return redirect()->back()->with('success', 'Mata Kuliah berhasil dipulihkan');
    }

    /**
     * Force Delete KelasMatakuliah
     */
    public function forceDeleteMatakuliah($id)
    {
        $km = \App\Models\KelasMatakuliah::onlyTrashed()->findOrFail($id);
        $km->forceDelete();

        return redirect()->back()->with('success', 'Mata Kuliah dihapus permanen');
    }

    /**
     * Helper to format Dosen Name with Gelar
     */
    private function formatNamaDosen($dosen)
    {
        if (!$dosen)
            return '-';
        // Use accessor if available, or manual format
        if (isset($dosen->nama_gelar)) {
            return $dosen->nama_gelar;
        }

        $nama = $dosen->nama ?? '-';
        $depan = $dosen->gelar_depan ? trim($dosen->gelar_depan) . ' ' : '';
        $belakang = $dosen->gelar_belakang ? ', ' . trim($dosen->gelar_belakang) : '';

        return $depan . $nama . $belakang;
    }
}
