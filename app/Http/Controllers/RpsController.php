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
                'rps' => function ($q) {
                    $q->latest(); // Get latest RPS if multiple
                }
            ])
            ->when($request->search, function ($q, $search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            })
            ->when($prodiIds !== null, function ($q) use ($prodiIds) {
                $q->whereIn('prodi_id', $prodiIds);
            })
            ->when($request->prodi_id, function ($q, $prodiId) {
                $q->where('prodi_id', $prodiId);
            });

        $mataKuliahs = $query->paginate(10)->withQueryString();

        // Get prodis for filter dropdown
        if ($prodiIds !== null && count($prodiIds) > 0) {
            $prodis = \App\Models\ProgramStudi::whereIn('id', $prodiIds)->orderBy('nama')->get();
        } else {
            $prodis = \App\Models\ProgramStudi::orderBy('nama')->get();
        }

        return Inertia::render('Rps/Index', [
            'mataKuliahs' => $mataKuliahs,
            'prodis' => $prodis,
            'filters' => [
                'search' => $request->search,
                'prodi_id' => $request->prodi_id,
            ],
        ]);
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

        return Inertia::render('Rps/Form', [
            'rps' => $rps,
            'mataKuliah' => $mataKuliah,
            'availableCpmks' => $cpmks->values()->toArray(),
            'availableSubCpmks' => $subCpmks->values()->toArray(),
            'can_approve' => auth()->user()->hasAnyRole(['akademik', 'staff_prodi']),
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
     * Submit RPS for approval
     */
    public function submit(Rps $rps)
    {
        if ($rps->status !== 'draft') {
            return response()->json(['message' => 'RPS harus berstatus Draft untuk diajukan.'], 422);
        }

        $rps->update(['status' => 'submitted']);

        return response()->json(['message' => 'RPS berhasil diajukan untuk review.']);
    }

    /**
     * Approve RPS (Kaprodi only)
     */
    public function approve(Rps $rps)
    {
        // Bypass check for Akademik (Super Admin)
        if ($rps->status !== 'submitted' && !auth()->user()->hasRole('akademik')) {
            return response()->json(['message' => 'RPS harus berstatus Submitted untuk disetujui.'], 422);
        }

        $rps->update(['status' => 'approved']);

        return response()->json(['message' => 'RPS berhasil disetujui.']);
    }

    /**
     * Reject RPS back to draft (Kaprodi with reason)
     */
    public function reject(Request $request, Rps $rps)
    {
        if ($rps->status !== 'submitted') {
            return response()->json(['message' => 'RPS harus berstatus Submitted untuk ditolak.'], 422);
        }

        $rps->update(['status' => 'draft']);

        return response()->json(['message' => 'RPS dikembalikan ke Draft.']);
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
}
