<?php

namespace App\Http\Controllers;

use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\Rps;
use App\Models\KurikulumAktif;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RpsController extends Controller
{
    public function index(Request $request)
    {
        // Get Active Semester/TA
        $activeTa = TahunAkademik::where('is_active', true)->first();
        $user = auth()->user();

        // Determine prodi restrictions based on role
        $prodiIds = null;
        if ($user->hasRole('mahasiswa')) {
            $mahasiswa = \App\Models\Mahasiswa::where('user_id', $user->id)->first();
            $prodiIds = $mahasiswa ? [$mahasiswa->prodi_id] : [];
        } elseif ($user->hasRole('staff_prodi')) {
            $prodiIds = $user->prodis()->pluck('program_studis.id')->toArray();
        } elseif ($user->hasRole('dosen')) {
            // Dosen sees MKs they are assigned to (via kelas_mk_dosen) or all for now
            // For simplicity, we'll show all MKs - can be refined later
            $prodiIds = null;
        }

        $query = MataKuliah::query()
            ->with([
                'prodi',
                'rps.dosen', // Creator
                'rps.pengembang', // Team Teaching (Dosen objects directly)
            ]);

        // Filter by Search
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        });

        // Filter by Prodi
        $query->when($prodiIds !== null, function ($q) use ($prodiIds) {
            $q->whereIn('prodi_id', $prodiIds);
        });
        $query->when($request->prodi_id, function ($q, $prodiId) {
            $q->where('prodi_id', $prodiId);
        });

        // Filter by Status (Server-side)
        if ($request->filter && $request->filter !== 'all') {
            $status = $request->filter;
            if ($status === 'none') {
                $query->doesntHave('rps');
            } else {
                $query->whereHas('rps', function ($q) use ($status) {
                    $q->where('approval_status', $status);
                });
            }
        }

        // Limit per page
        $perPage = $request->input('per_page', 10);
        $mataKuliahs = $query->paginate($perPage)->withQueryString();

        // Get prodis for filter dropdown
        if ($prodiIds !== null && count($prodiIds) > 0) {
            $prodis = \App\Models\ProgramStudi::whereIn('id', $prodiIds)->orderBy('nama')->get();
        } else {
            $prodis = \App\Models\ProgramStudi::orderBy('nama')->get();
        }

        // Get all Dosen for Developer Dropdown (Live Edit)
        // All dosens can be selected, we use dosen_id in pivot table
        $allDosens = \App\Models\Dosen::orderBy('nama')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id, // dosen_id
                    'nama_gelar' => $d->nama_gelar,
                    'nama' => $d->nama,
                ];
            });

        return Inertia::render('Rps/Index', [
            'mataKuliahs' => $mataKuliahs,
            'prodis' => $prodis,
            'allDosens' => $allDosens,
            'canFilterProdi' => $prodiIds === null, // Admin can filter, others cannot
            'filters' => [
                'search' => $request->search,
                'prodi_id' => $request->prodi_id,
                'filter' => $request->filter ?? 'all',
                'per_page' => $perPage
            ],
        ]);
    }

    /**
     * Live Update RPS Meta (Pengembang, Date)
     */
    public function updateMeta(Request $request, Rps $rps)
    {
        $validated = $request->validate([
            'field' => 'required|in:pengembang,tanggal_penyusunan',
            'value' => 'nullable',
        ]);

        if ($validated['field'] === 'pengembang') {
            // Value expects array of dosen_ids
            $dosenIds = $validated['value'] ?? [];
            if (!is_array($dosenIds))
                return response()->json(['message' => 'Invalid data'], 422);

            // Filter empty values to prevent SQL errors
            $dosenIds = array_filter($dosenIds, fn($id) => !empty($id) && is_numeric($id));

            $rps->pengembang()->sync($dosenIds);
            $msg = 'Pengembang RPS berhasil diperbarui';
        } else {
            // Tanggal Penyusunan
            $rps->update(['tanggal_penyusunan' => $validated['value']]);
            $msg = 'Tanggal penyusunan berhasil diperbarui';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function create(Request $request)
    {
        // Get MataKuliah from query string
        $mkId = $request->input('mata_kuliah');
        $mataKuliah = MataKuliah::findOrFail($mkId);

        // Check if RPS exists?
        $existingRps = Rps::where('mata_kuliah_id', $mataKuliah->id)->first();
        if ($existingRps) {
            return redirect()->route('rps.edit', $existingRps);
        }

        // Load Available CPMKs for this MK
        $cpmks = \App\Models\Cpmk::where('mata_kuliah_id', $mataKuliah->id)
            ->with(['cpl', 'subCpmks'])
            ->get();
        // Flatten SubCPMKs
        $subCpmks = $cpmks->flatMap(fn($c) => $c->subCpmks);

        return Inertia::render('Rps/Form', [
            'mataKuliah' => $mataKuliah->load('prodi'),
            'rps' => null,
            'availableCpmks' => $cpmks,
            'availableSubCpmks' => $subCpmks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'semester_id' => 'required|exists:semesters,id', // Should be current active semester
            'deskripsi' => 'nullable|string',
        ]);

        $rps = Rps::create([
            'mata_kuliah_id' => $validated['mata_kuliah_id'],
            'semester_id' => $validated['semester_id'], // Get from TA Active normally
            'dosen_id' => auth()->id(), // Current user as creator
            'tanggal_penyusunan' => now(),
            'status' => 'draft',
            'nomor' => 'RPS-' . time(), // Auto gen
        ]);

        // Auto-populate Pengembang from Jadwal Dosen (Kelas MK Dosen)
        $this->autoPopulatePengembangFromJadwal($rps);

        // Return JSON for AJAX calls (Magic Generator auto-create)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $rps->id,
                'message' => 'RPS Draft created'
            ]);
        }

        return redirect()->route('rps.edit', $rps)->with('success', 'RPS Draft created');
    }

    public function edit(Rps $rps)
    {
        // Load relationships
        $rps->load(['mataKuliah.prodi', 'details.subCpmk', 'semester']);

        // Explicitly load MataKuliah (ensure not null)
        $mataKuliah = $rps->mataKuliah;

        // Load CPMKs with SubCPMKs
        $cpmks = $mataKuliah
            ? \App\Models\Cpmk::where('mata_kuliah_id', $mataKuliah->id)
                ->with(['cpl', 'subCpmks'])
                ->get()
            : collect();

        // Flatten SubCPMKs for easy selection
        $subCpmks = $cpmks->flatMap(fn($c) => $c->subCpmks);

        // Debug log
        \Log::info('RPS Edit Props', [
            'rps_id' => $rps->id,
            'mk_id' => $rps->mata_kuliah_id,
            'mataKuliah' => $mataKuliah ? $mataKuliah->toArray() : null,
            'cpmks_count' => $cpmks->count(),
        ]);

        $user = auth()->user();
        $prodi = $rps->mataKuliah->prodi;

        $isAdmin = $user->hasAnyRole(['akademik', 'staff_prodi', 'admin', 'administrator', 'staf']);
        $isGkm = $user->dosen?->id === $prodi?->gkm_id;
        $isKaprodi = $user->dosen?->id === $prodi?->kaprodi_id;

        return Inertia::render('Rps/Form', [
            'rps' => $rps,
            'mataKuliah' => $mataKuliah,
            'availableCpmks' => $cpmks->values()->toArray(),
            'availableSubCpmks' => $subCpmks->values()->toArray(),
            'permissions' => [
                'can_approve' => $isAdmin || $isGkm || $isKaprodi,
                'is_admin' => $isAdmin,
                'is_gkm' => $isGkm,
                'is_kaprodi' => $isKaprodi,
            ],
            // Keep old prop for backward compatibility if needed, but permissions object is better
            'can_approve' => $isAdmin || $isGkm || $isKaprodi,
        ]);
    }

    public function update(Request $request, Rps $rps)
    {
        $validated = $request->validate([
            'deskripsi' => 'nullable|string',
            'bahan_kajian' => 'nullable|string',
            'pustaka_utama' => 'nullable|string',
            'pustaka_pendukung' => 'nullable|string',
            'details' => 'array',
            'details.*.pertemuan' => 'required|integer',
            'details.*.materi' => 'nullable|string',
            'details.*.metode' => 'nullable|string',
            'details.*.indikator' => 'nullable|string',
            'details.*.sub_cpmk_id' => 'nullable|exists:sub_cpmks,id',
            'details.*.bobot_nilai' => 'nullable|numeric',
        ]);

        $rps->update([
            'deskripsi' => $validated['deskripsi'],
            'bahan_kajian' => $validated['bahan_kajian'],
            'pustaka_utama' => $validated['pustaka_utama'],
            'pustaka_pendukung' => $validated['pustaka_pendukung'],
            'tanggal_penyusunan' => now(), // update timestamp
        ]);

        // Sync Details
        // Simple approach: delete all and recreate? Or update/create?
        // update/create is better to preserve IDs if needed, but simple is often robust enough for full form submit.
        // Let's use updateOrCreate based on 'pertemuan'.

        foreach ($validated['details'] ?? [] as $detail) {
            $rps->details()->updateOrCreate(
                ['pertemuan' => $detail['pertemuan']],
                [
                    'materi' => $detail['materi'],
                    'metode' => $detail['metode'],
                    'indikator' => $detail['indikator'],
                    'sub_cpmk_id' => $detail['sub_cpmk_id'],
                    'bobot_nilai' => $detail['bobot_nilai'] ?? 0,
                ]
            );
        }

        return redirect()->back()->with('success', 'RPS berhasil diperbarui');
    }

    /**
     * Submit RPS for approval (by Dosen)
     */
    public function submit(Rps $rps)
    {
        if (!$rps->canBeSubmitted()) {
            return response()->json(['message' => 'RPS harus berstatus Draft atau Revision untuk diajukan.'], 422);
        }

        $rps->update(['approval_status' => Rps::STATUS_SUBMITTED]);

        return response()->json(['message' => 'RPS berhasil diajukan untuk review GKM.']);
    }

    /**
     * Approve RPS by GKM/Koordinator RMK
     */
    public function approveByGkm(Request $request, Rps $rps)
    {
        if (!$rps->canBeApprovedByGkm()) {
            return response()->json(['message' => 'RPS harus berstatus Submitted untuk di-review GKM.'], 422);
        }

        // Load prodi relationship to check GKM
        $rps->load('mataKuliah.prodi');
        $prodi = $rps->mataKuliah?->prodi;
        $user = auth()->user();

        // Check if user is the assigned GKM for this prodi OR has admin role
        $isGkm = $user->dosen?->id === $prodi?->gkm_id;
        $isAdmin = $user->hasAnyRole(['akademik', 'admin', 'administrator', 'staf']);

        if (!$isGkm && !$isAdmin) {
            return response()->json(['message' => 'Anda bukan GKM untuk program studi ini.'], 403);
        }

        $gkmDosenId = $prodi?->gkm_id ?? $user->dosen?->id;

        $rps->update([
            'approval_status' => Rps::STATUS_GKM_APPROVED,
            'approved_by_gkm_id' => $gkmDosenId,
            'approved_by_gkm_at' => now(),
            'gkm_notes' => $request->input('notes'),
        ]);

        return response()->json(['message' => 'RPS berhasil di-approve oleh GKM. Lanjut ke Kaprodi.']);
    }

    /**
     * Approve RPS by Kaprodi (Final Approval)
     */
    public function approveByKaprodi(Request $request, Rps $rps)
    {
        if (!$rps->canBeApprovedByKaprodi()) {
            return response()->json(['message' => 'RPS harus sudah di-approve GKM terlebih dahulu.'], 422);
        }

        // Load prodi relationship to check Kaprodi
        $rps->load('mataKuliah.prodi');
        $prodi = $rps->mataKuliah?->prodi;
        $user = auth()->user();

        // Check if user is the assigned Kaprodi for this prodi OR has admin role
        $isKaprodi = $user->dosen?->id === $prodi?->kaprodi_id;
        $isAdmin = $user->hasAnyRole(['akademik', 'admin', 'administrator', 'staf']);

        if (!$isKaprodi && !$isAdmin) {
            return response()->json(['message' => 'Anda bukan Kaprodi untuk program studi ini.'], 403);
        }

        $kaprodiDosenId = $prodi?->kaprodi_id ?? $user->dosen?->id;

        // Generate verification code
        $verificationCode = $rps->generateVerificationCode();

        $rps->update([
            'approval_status' => Rps::STATUS_APPROVED,
            'verification_code' => $verificationCode,
            'approved_by_kaprodi_id' => $kaprodiDosenId,
            'approved_by_kaprodi_at' => now(),
            'kaprodi_notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => 'RPS berhasil disahkan.',
            'verification_code' => $verificationCode,
        ]);
    }

    /**
     * Bypass approve (Admin/Staf Prodi only) - Skip GKM & Kaprodi steps
     */
    public function bypassApprove(Request $request, Rps $rps)
    {
        // Only allow admin or staf roles
        $user = auth()->user();
        if (!$user->hasAnyRole(['akademik', 'admin', 'administrator', 'staf'])) {
            return response()->json(['message' => 'Anda tidak memiliki izin untuk bypass approval.'], 403);
        }

        // Generate verification code
        $verificationCode = $rps->generateVerificationCode();

        $rps->update([
            'approval_status' => Rps::STATUS_APPROVED,
            'verification_code' => $verificationCode,
            'approved_by_gkm_at' => now(),
            'approved_by_kaprodi_at' => now(),
            'gkm_notes' => 'Bypass oleh ' . $user->name,
            'kaprodi_notes' => 'Bypass oleh ' . $user->name,
        ]);

        return response()->json([
            'message' => 'RPS berhasil di-approve (bypass).',
            'verification_code' => $verificationCode,
        ]);
    }

    /**
     * Request revision (by GKM or Kaprodi)
     */
    public function requestRevision(Request $request, Rps $rps)
    {
        $request->validate(['notes' => 'required|string|min:10']);

        if (!in_array($rps->approval_status, [Rps::STATUS_SUBMITTED, Rps::STATUS_GKM_APPROVED])) {
            return response()->json(['message' => 'RPS tidak dalam status yang dapat diminta revisi.'], 422);
        }

        $rps->update([
            'approval_status' => Rps::STATUS_REVISION,
            'kaprodi_notes' => $request->input('notes'),
        ]);

        return response()->json(['message' => 'RPS dikembalikan untuk revisi.']);
    }
    /**
     * Delete RPS and all related data (Details, Sub-CPMKs)
     */
    public function destroy(Rps $rps)
    {
        // Delete related Sub-CPMKs if they were created specifically for this MK? 
        // Sub-CPMKs are linked to MataKuliah, not directly to RPS ID in the DB schema usually, 
        // but the user requested "delete smua data info umum, sub cpmk dan pertemuan".
        // We should check if we should delete ALL Sub-CPMKs for this Mata Kuliah.
        // Assuming this RPS creation flow is the primary manager of Sub-CPMKs for this MK.

        $mkId = $rps->mata_kuliah_id;

        try {
            \DB::transaction(function () use ($rps, $mkId) {
                // 1. Delete Details (Pertemuan) - automatically handled by cascade usually, but good to be explicit or if no cascade
                $rps->details()->delete();

                // 2. Delete Sub-CPMKs - REMOVED. Sub-CPMKs are now part of Kurikulum and should persist.
                // \App\Models\SubCpmk::where('mata_kuliah_id', $mkId)->delete();

                // 3. Delete RPS (Info Umum)
                $rps->delete();
            });

            return redirect()->route('rps.index')->with('success', 'RPS dan data terkait berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    /**
     * Auto-populate Pengembang RPS from Dosen Pengampu di Jadwal
     * Only populates if no pengembang already set
     */
    protected function autoPopulatePengembangFromJadwal(Rps $rps): void
    {
        // Skip if pengembang already set
        if ($rps->pengembang()->count() > 0) {
            return;
        }

        // Get current active semester
        $activeSemester = \App\Models\Semester::where('is_active', true)->first();
        if (!$activeSemester) {
            return;
        }

        // Find Dosen assigned to this Mata Kuliah in current semester's Jadwal
        // kelas_matakuliah -> kelas_mk_dosen -> dosen
        $dosenIds = \App\Models\KelasMkDosen::whereHas('kelasMatakuliah', function ($q) use ($rps, $activeSemester) {
            $q->where('mata_kuliah_id', $rps->mata_kuliah_id)
                ->whereHas('kelas', function ($k) use ($activeSemester) {
                    $k->where('semester_id', $activeSemester->id);
                });
        })
            ->pluck('dosen_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Sync to pivot table
        if (!empty($dosenIds)) {
            $rps->pengembang()->attach($dosenIds);
        }
    }
}
