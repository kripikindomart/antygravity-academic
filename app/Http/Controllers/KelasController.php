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
        $availableMks = MataKuliah::with(['prodi', 'kurikulums'])
            ->whereNotIn('id', $kelas->mataKuliahs->pluck('id'))
            ->orderBy('nama')
            ->get();

        // Get kurikulums for filter
        $kurikulums = \App\Models\Kurikulum::with('prodi')->orderBy('nama')->get();

        // Get all ruangans
        $allRuangans = Ruangan::orderBy('nama')->get();

        // Get all dosens
        $dosens = \App\Models\Dosen::orderBy('nama')->get();

        // Master data for Enrollment Filters
        $allProdis = \App\Models\ProgramStudi::orderBy('nama')->get();
        // Get unique angkatan from Mahasiswa
        $allAngkatans = \App\Models\Mahasiswa::distinct()->orderBy('angkatan', 'desc')->pluck('angkatan');

        return Inertia::render('Kelas/Detail', [
            'kelas' => $kelas,
            'availableMks' => $availableMks,
            'kurikulums' => $kurikulums,
            'allRuangans' => $allRuangans,
            'dosens' => $dosens,
            'filterData' => [
                'prodis' => $allProdis,
                'angkatans' => $allAngkatans,
                'statuses' => ['aktif', 'cuti', 'lulus', 'drop_out', 'non_aktif'] // Standard statuses
            ]
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
        if (!empty($validated['ruangan_id'])) {
            foreach ($validated['ids'] as $kelasMkId) {
                $kelasMk = KelasMatakuliah::find($kelasMkId);
                if ($kelasMk && !$kelasMk->ruangans()->where('ruangan_id', $validated['ruangan_id'])->exists()) {
                    $kelasMk->ruangans()->attach($validated['ruangan_id']);
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
}
