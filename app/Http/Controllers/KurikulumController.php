<?php

namespace App\Http\Controllers;

use App\Models\Kurikulum;
use App\Models\Cpl;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KurikulumController extends Controller
{
    /**
     * Display kurikulum list
     */
    public function index(Request $request)
    {
        $query = Kurikulum::with(['prodi', 'cpls', 'mataKuliahs'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->when($request->prodi, function ($q, $prodiId) {
                $q->where('prodi_id', $prodiId);
            })
            ->when($request->status === 'active', function ($q) {
                $q->where('is_active', true);
            })
            ->when($request->status === 'inactive', function ($q) {
                $q->where('is_active', false);
            })
            ->orderBy('tahun', 'desc')
            ->orderBy('nama');

        $kurikulums = $query->paginate(10)->withQueryString();

        // Get user's bound prodi (for staff_prodi role)
        $userProdiId = null;
        $user = $request->user();
        if ($user && $user->prodis()->count() === 1) {
            $userProdiId = $user->prodis()->first()->id;
        }

        return Inertia::render('Kurikulum/Index', [
            'kurikulums' => $kurikulums,
            'prodis' => ProgramStudi::select('id', 'nama', 'kode', 'jenjang')->orderBy('jenjang')->orderBy('nama')->get(),
            'filters' => $request->only(['search', 'prodi', 'status']),
            'userProdiId' => $userProdiId,
        ]);
    }

    /**
     * Store new kurikulum
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', 'unique:kurikulums,kode'],
            'nama' => ['required', 'string', 'max:200'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'total_sks_wajib' => ['integer', 'min:0'],
            'total_sks_pilihan' => ['integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        Kurikulum::create($validated);

        return back()->with('success', 'Kurikulum berhasil ditambahkan.');
    }

    /**
     * Update kurikulum
     */
    public function update(Request $request, Kurikulum $kurikulum)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', 'unique:kurikulums,kode,' . $kurikulum->id],
            'nama' => ['required', 'string', 'max:200'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'total_sks_wajib' => ['integer', 'min:0'],
            'total_sks_pilihan' => ['integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $kurikulum->update($validated);

        return back()->with('success', 'Kurikulum berhasil diperbarui.');
    }

    /**
     * Delete kurikulum
     */
    public function destroy(Kurikulum $kurikulum)
    {
        $kurikulum->delete();

        return back()->with('success', 'Kurikulum berhasil dihapus.');
    }

    /**
     * Show kurikulum detail with CPL
     */
    public function show(Kurikulum $kurikulum)
    {
        $kurikulum->load(['prodi', 'cpls.cpmks.mataKuliah', 'cpls.cpmks.subCpmks', 'mataKuliahs', 'profilLulusans.cpls']); // withPivot semester handled in model relation?

        return Inertia::render('Kurikulum/Show', [
            'kurikulum' => $kurikulum,
            'prodis' => ProgramStudi::select('id', 'nama', 'jenjang')->orderBy('jenjang')->orderBy('nama')->get(),
            'cplMkMapping' => \Illuminate\Support\Facades\DB::table('cpl_mata_kuliah')
                ->where('kurikulum_id', $kurikulum->id)
                ->get(['cpl_id', 'mata_kuliah_id'])
                ->map(fn($item) => $item->cpl_id . '-' . $item->mata_kuliah_id)
                ->values()
                ->toArray(),
        ]);
    }

    public function toggleCplMk(Request $request, Kurikulum $kurikulum)
    {
        $request->validate([
            'cpl_id' => 'required|exists:cpls,id',
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'attached' => 'required|boolean'
        ]);

        if ($request->attached) {
            \Illuminate\Support\Facades\DB::table('cpl_mata_kuliah')->insertOrIgnore([
                'kurikulum_id' => $kurikulum->id,
                'cpl_id' => $request->cpl_id,
                'mata_kuliah_id' => $request->mata_kuliah_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            \Illuminate\Support\Facades\DB::table('cpl_mata_kuliah')
                ->where('kurikulum_id', $kurikulum->id)
                ->where('cpl_id', $request->cpl_id)
                ->where('mata_kuliah_id', $request->mata_kuliah_id)
                ->delete();
        }

        return back();
    }

    /**
     * Store CPL
     */
    public function storeCpl(Request $request, Kurikulum $kurikulum)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'deskripsi' => ['required', 'string'],
            'kategori' => ['required', 'in:sikap,pengetahuan,keterampilan_umum,keterampilan_khusus'],
            'urutan' => ['integer', 'min:0'],
        ]);

        $cpl = $kurikulum->cpls()->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'CPL berhasil ditambahkan.',
                'cpl' => $cpl
            ]);
        }

        return back()->with('success', 'CPL berhasil ditambahkan.');
    }

    /**
     * Update CPL
     */
    public function updateCpl(Request $request, Cpl $cpl)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'deskripsi' => ['required', 'string'],
            'kategori' => ['required', 'in:sikap,pengetahuan,keterampilan_umum,keterampilan_khusus'],
            'urutan' => ['integer', 'min:0'],
        ]);

        $cpl->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'CPL berhasil diperbarui.',
                'cpl' => $cpl
            ]);
        }

        return back()->with('success', 'CPL berhasil diperbarui.');
    }

    /**
     * Delete CPL
     */
    public function destroyCpl(Request $request, Cpl $cpl)
    {
        $cpl->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'CPL berhasil dihapus.'
            ]);
        }

        return back()->with('success', 'CPL berhasil dihapus.');
    }

    /**
     * Get all MK for prodi (for assignment modal)
     */
    public function getAvailableMk(Request $request, Kurikulum $kurikulum)
    {
        $assignedMkIds = $kurikulum->mataKuliahs()->pluck('mata_kuliah_id');
        $prodiId = $request->input('prodi_id', $kurikulum->prodi_id);

        $availableMk = \App\Models\MataKuliah::where('prodi_id', $prodiId)
            ->whereNotIn('id', $assignedMkIds)
            ->orderBy('semester')
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'semester', 'sks_teori', 'sks_praktik', 'jenis']);

        return response()->json($availableMk);
    }

    /**
     * Assign MK to Kurikulum
     */
    public function assignMk(Request $request, Kurikulum $kurikulum)
    {
        $validated = $request->validate([
            'mata_kuliah_ids' => ['required', 'array'],
            'mata_kuliah_ids.*' => ['exists:mata_kuliahs,id'],
            'semester' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        foreach ($validated['mata_kuliah_ids'] as $mkId) {
            // Attach if not exists, using specific semester
            if (!$kurikulum->mataKuliahs()->where('mata_kuliah_id', $mkId)->exists()) {
                $kurikulum->mataKuliahs()->attach($mkId, ['semester' => $validated['semester']]);
            }
        }

        return redirect()->back()->with('success', count($validated['mata_kuliah_ids']) . ' Mata Kuliah ditambahkan ke Semester ' . $validated['semester']);
    }

    /**
     * Remove MK from Kurikulum
     */
    public function removeMk(Kurikulum $kurikulum, $mkId)
    {
        $kurikulum->mataKuliahs()->detach($mkId);

        return back()->with('success', 'Mata Kuliah berhasil dihapus dari kurikulum.');
    }

    public function removeMkBulk(Request $request, Kurikulum $kurikulum)
    {
        $validated = $request->validate([
            'mata_kuliah_ids' => ['required', 'array'],
            'mata_kuliah_ids.*' => ['exists:mata_kuliahs,id'],
        ]);

        $kurikulum->mataKuliahs()->detach($validated['mata_kuliah_ids']);

        return back()->with('success', count($validated['mata_kuliah_ids']) . ' Mata Kuliah berhasil dihapus dari kurikulum.');
    }

    /**
     * Duplicate kurikulum (template feature)
     */
    public function duplicate(Kurikulum $kurikulum)
    {
        $newKurikulum = $kurikulum->replicate();
        $newKurikulum->kode = $kurikulum->kode . '-COPY';
        $newKurikulum->nama = $kurikulum->nama . ' (Copy)';
        $newKurikulum->is_active = false;
        $newKurikulum->save();

        // Duplicate CPLs
        foreach ($kurikulum->cpls as $cpl) {
            $newCpl = $cpl->replicate();
            $newCpl->kurikulum_id = $newKurikulum->id;
            $newCpl->save();
        }

        // Duplicate MK assignments
        foreach ($kurikulum->mataKuliahs as $mk) {
            $newKurikulum->mataKuliahs()->attach($mk->id, [
                'semester_rekomendasi' => $mk->pivot->semester_rekomendasi,
            ]);
        }

        return redirect("/kurikulum/{$newKurikulum->id}")->with('success', 'Kurikulum berhasil diduplikasi.');
    }
}
