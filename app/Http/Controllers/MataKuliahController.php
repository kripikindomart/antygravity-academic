<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MataKuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MataKuliah::with(['prodi', 'prasyarat'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('nama_en', 'like', "%{$search}%");
                });
            })
            ->when($request->prodi_id, function ($query, $prodiId) {
                $query->where('prodi_id', $prodiId);
            })
            ->when($request->semester, function ($query, $semester) {
                $query->where('semester', $semester);
            })
            ->when($request->jenis, function ($query, $jenis) {
                $query->where('jenis', $jenis);
            });

        // Add filter for status if needed, defaults to all if not specified, 
        // but schema has is_active default true.
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        $mataKuliahs = $query->orderBy('prodi_id')
            ->orderBy('semester')
            ->orderBy('kode')
            ->paginate(10)
            ->withQueryString();

        $prodis = ProgramStudi::where('is_active', true)->orderBy('nama')->get();

        // For prasyarat dropdown, we might want all active MKs. 
        // Ideally filter by selected Prodi in frontend, but passing all here for simplicity initially
        // or fetch via API if too many. Let's pass simple all active list (id, kode, nama).
        $allMataKuliahs = MataKuliah::where('is_active', true)
            ->select('id', 'kode', 'nama', 'prodi_id')
            ->get();

        return Inertia::render('MasterData/MataKuliah/Index', [
            'mataKuliahs' => $mataKuliahs,
            'prodis' => $prodis,
            'allMataKuliahs' => $allMataKuliahs, // Optimized in future if heavy
            'filters' => $request->only(['search', 'prodi_id', 'semester', 'jenis', 'status']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', 'unique:mata_kuliahs,kode'],
            'nama' => ['required', 'string', 'max:200'],
            'nama_en' => ['nullable', 'string', 'max:200'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'semester' => ['required', 'integer', 'min:1'],
            'jenis' => ['required', 'in:wajib,pilihan'],
            'deskripsi' => ['nullable', 'string'],
            'prasyarat_id' => ['nullable', 'exists:mata_kuliahs,id'],
            'is_active' => ['boolean'],
        ]);

        if (isset($validated['prasyarat_id']) && $validated['kode'] === $request->input('prasyarat_kode')) {
            // Logic validation on self-reference is handled by ID usually. 
            // We can't check ID self-ref on create.
        }

        MataKuliah::create($validated);

        return redirect()->route('mata-kuliah.index')
            ->with('success', 'Mata Kuliah berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MataKuliah $mataKuliah)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', "unique:mata_kuliahs,kode,{$mataKuliah->id}"],
            'nama' => ['required', 'string', 'max:200'],
            'nama_en' => ['nullable', 'string', 'max:200'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'semester' => ['required', 'integer', 'min:1'],
            'jenis' => ['required', 'in:wajib,pilihan'],
            'deskripsi' => ['nullable', 'string'],
            'prasyarat_id' => ['nullable', 'exists:mata_kuliahs,id', "not_in:{$mataKuliah->id}"],
            'is_active' => ['boolean'],
        ]);

        $mataKuliah->update($validated);

        return redirect()->route('mata-kuliah.index')
            ->with('success', 'Mata Kuliah berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MataKuliah $mataKuliah)
    {
        // Check if used in kurikulum or has dependent MKs?
        // Soft delete handles it safely usually.
        $mataKuliah->delete();

        return redirect()->route('mata-kuliah.index')
            ->with('success', 'Mata Kuliah berhasil dihapus.');
    }
}
