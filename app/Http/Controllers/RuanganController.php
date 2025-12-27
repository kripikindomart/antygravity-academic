<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RuanganController extends Controller
{
    /**
     * Display a listing of ruangan.
     */
    public function index(Request $request)
    {
        $query = Ruangan::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                    ->orWhere('kode', 'like', "%{$request->search}%");
            });
        }

        if ($request->tipe) {
            $query->where('tipe', $request->tipe);
        }

        $ruangans = $query->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterData/Ruangan/Index', [
            'ruangans' => $ruangans,
            'filters' => $request->only(['search', 'tipe']),
        ]);
    }

    /**
     * Store a newly created ruangan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:ruangans,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:kelas,lab,aula,ruang_rapat,lainnya'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'gedung' => ['nullable', 'string', 'max:255'],
            'lantai' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        Ruangan::create($validated);

        return redirect()->route('ruangan.index')
            ->with('success', 'Ruangan berhasil ditambahkan.');
    }

    /**
     * Update the specified ruangan.
     */
    public function update(Request $request, Ruangan $ruangan)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', "unique:ruangans,kode,{$ruangan->id}"],
            'nama' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:kelas,lab,aula,ruang_rapat,lainnya'],
            'kapasitas' => ['required', 'integer', 'min:1'],
            'gedung' => ['nullable', 'string', 'max:255'],
            'lantai' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $ruangan->update($validated);

        return redirect()->route('ruangan.index')
            ->with('success', 'Ruangan berhasil diperbarui.');
    }

    /**
     * Remove the specified ruangan.
     */
    public function destroy(Ruangan $ruangan)
    {
        $ruangan->delete();

        return redirect()->route('ruangan.index')
            ->with('success', 'Ruangan berhasil dihapus.');
    }
}
