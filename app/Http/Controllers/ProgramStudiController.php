<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramStudiController extends Controller
{
    /**
     * Display a listing of program studi.
     */
    public function index(Request $request)
    {
        $query = ProgramStudi::query()
            ->with(['kaprodi.dosen', 'sekprodi.dosen']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                    ->orWhere('kode', 'like', "%{$request->search}%");
            });
        }

        if ($request->jenjang) {
            $query->where('jenjang', $request->jenjang);
        }

        $programStudis = $query->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/ProgramStudi/Index', [
            'programStudis' => $programStudis,
            'filters' => $request->only(['search', 'jenjang']),
        ]);
    }

    /**
     * Store a newly created program studi.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:program_studis,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', 'in:S1,S2,S3'],
            'akreditasi' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        ProgramStudi::create($validated);

        return redirect()->route('prodi.index')
            ->with('success', 'Program Studi berhasil ditambahkan.');
    }

    /**
     * Update the specified program studi.
     */
    public function update(Request $request, ProgramStudi $programStudi)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', "unique:program_studis,kode,{$programStudi->id}"],
            'nama' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', 'in:S1,S2,S3'],
            'akreditasi' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $programStudi->update($validated);

        return redirect()->route('prodi.index')
            ->with('success', 'Program Studi berhasil diperbarui.');
    }

    /**
     * Remove the specified program studi.
     */
    public function destroy(ProgramStudi $programStudi)
    {
        $programStudi->delete();

        return redirect()->route('prodi.index')
            ->with('success', 'Program Studi berhasil dihapus.');
    }
}
