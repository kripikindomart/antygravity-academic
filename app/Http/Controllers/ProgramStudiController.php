<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Models\Dosen;
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
            ->with(['kaprodi', 'sekretaris', 'gkm']);

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
     * Show edit form for a program studi.
     */
    public function edit(ProgramStudi $programStudi)
    {
        $programStudi->load(['kaprodi', 'sekretaris', 'gkm', 'dosens']);

        // Get ALL active dosens from all prodis (Kaprodi/Sekprodi can be from any homebase - PLT case)
        $dosens = Dosen::with('prodi:id,kode')
            ->where('status', 'aktif')
            ->orderBy('nama')
            ->get(['id', 'prodi_id', 'nama', 'nidn', 'gelar_depan', 'gelar_belakang']);

        return Inertia::render('MasterData/ProgramStudi/Edit', [
            'programStudi' => $programStudi,
            'dosens' => $dosens,
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
            'jenjang' => ['required', 'in:D3,D4,S1,S2,S3'],
            'visi' => ['nullable', 'string'],
            'misi' => ['nullable', 'string'],
            'tujuan' => ['nullable', 'string'],
            'akreditasi' => ['nullable', 'string', 'max:50'],
            'no_sk_akreditasi' => ['nullable', 'string', 'max:100'],
            'tanggal_akreditasi' => ['nullable', 'date'],
            'masa_berlaku_akreditasi' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:200'],
            'kaprodi_id' => ['nullable', 'exists:dosens,id'],
            'is_kaprodi_plt' => ['boolean'],
            'sekretaris_id' => ['nullable', 'exists:dosens,id'],
            'is_sekretaris_plt' => ['boolean'],
            'gkm_id' => ['nullable', 'exists:dosens,id'],
            'staf_prodi' => ['nullable', 'array'],
            'staf_prodi.*' => ['string', 'max:200'],
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
            'jenjang' => ['required', 'in:D3,D4,S1,S2,S3'],
            'visi' => ['nullable', 'string'],
            'misi' => ['nullable', 'string'],
            'tujuan' => ['nullable', 'string'],
            'akreditasi' => ['nullable', 'string', 'max:50'],
            'no_sk_akreditasi' => ['nullable', 'string', 'max:100'],
            'tanggal_akreditasi' => ['nullable', 'date'],
            'masa_berlaku_akreditasi' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:200'],
            'kaprodi_id' => ['nullable', 'exists:dosens,id'],
            'is_kaprodi_plt' => ['boolean'],
            'sekretaris_id' => ['nullable', 'exists:dosens,id'],
            'is_sekretaris_plt' => ['boolean'],
            'gkm_id' => ['nullable', 'exists:dosens,id'],
            'staf_prodi' => ['nullable', 'array'],
            'staf_prodi.*' => ['string', 'max:200'],
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
