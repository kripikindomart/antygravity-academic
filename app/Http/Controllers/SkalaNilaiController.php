<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Models\SkalaNilai;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SkalaNilaiController extends Controller
{
    public function index(Request $request)
    {
        $query = SkalaNilai::with('prodi');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('huruf', 'like', "%$search%");
        }

        if ($request->has('prodi_id') && $request->prodi_id) {
            $query->where('prodi_id', $request->prodi_id);
        }

        $skalaNilais = $query->orderBy('bobot', 'desc')->get();
        $prodis = ProgramStudi::select('id', 'nama', 'jenjang')->orderBy('nama')->get();

        return Inertia::render('MasterData/SkalaNilai/Index', [
            'skalaNilais' => $skalaNilais,
            'prodis' => $prodis,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => 'nullable|exists:program_studis,id',
            'huruf' => 'required|string|max:2',
            'bobot' => 'required|numeric|min:0|max:4',
            'min_nilai' => 'required|numeric|min:0|max:100',
            'max_nilai' => 'required|numeric|min:0|max:100|gte:min_nilai',
            'status_lulus' => 'boolean',
            'keterangan' => 'nullable|string',
        ]);

        SkalaNilai::create($validated);

        return redirect()->back()->with('success', 'Skala Nilai berhasil ditambahkan');
    }

    public function update(Request $request, SkalaNilai $skalaNilai)
    {
        $validated = $request->validate([
            'prodi_id' => 'nullable|exists:program_studis,id',
            'huruf' => 'required|string|max:2',
            'bobot' => 'required|numeric|min:0|max:4',
            'min_nilai' => 'required|numeric|min:0|max:100',
            'max_nilai' => 'required|numeric|min:0|max:100|gte:min_nilai',
            'status_lulus' => 'boolean',
            'keterangan' => 'nullable|string',
        ]);

        $skalaNilai->update($validated);

        return redirect()->back()->with('success', 'Skala Nilai berhasil diperbarui');
    }

    public function destroy(SkalaNilai $skalaNilai)
    {
        $skalaNilai->delete();

        return redirect()->back()->with('success', 'Skala Nilai berhasil dihapus');
    }
}
