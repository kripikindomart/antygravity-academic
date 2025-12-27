<?php

namespace App\Http\Controllers;

use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CpmkController extends Controller
{
    /**
     * Get CPMKs for a specific Mata Kuliah within a Kurikulum context.
     */
    public function getByKurikulumMataKuliah(Kurikulum $kurikulum, MataKuliah $mataKuliah)
    {
        $cpmks = Cpmk::where('mata_kuliah_id', $mataKuliah->id)
            ->whereHas('cpl', function ($q) use ($kurikulum) {
                $q->where('kurikulum_id', $kurikulum->id);
            })
            ->with(['cpl'])
            ->orderBy('urutan')
            ->orderBy('kode')
            ->get();

        return response()->json($cpmks);
    }

    /**
     * Store a new CPMK.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cpl_id' => 'required|exists:cpls,id',
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'bobot' => 'nullable|numeric|min:0',
            'urutan' => 'nullable|integer',
        ]);

        $cpmk = Cpmk::create($validated);

        return response()->json([
            'message' => 'CPMK berhasil ditambahkan.',
            'cpmk' => $cpmk->load('cpl'),
        ]);
    }

    /**
     * Update the specified CPMK.
     */
    public function update(Request $request, Cpmk $cpmk)
    {
        $validated = $request->validate([
            'cpl_id' => 'required|exists:cpls,id',
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'bobot' => 'nullable|numeric|min:0',
            'urutan' => 'nullable|integer',
        ]);

        $cpmk->update($validated);

        return response()->json([
            'message' => 'CPMK berhasil diperbarui.',
            'cpmk' => $cpmk->load('cpl'),
        ]);
    }

    /**
     * Remove the specified CPMK.
     */
    public function destroy(Cpmk $cpmk)
    {
        $cpmk->delete();

        return response()->json([
            'message' => 'CPMK berhasil dihapus.',
        ]);
    }
}
