<?php

namespace App\Http\Controllers;

use App\Models\TahunAkademik;
use App\Models\Semester;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TahunAkademikController extends Controller
{
    /**
     * Display a listing of tahun akademik.
     */
    public function index(Request $request)
    {
        $query = TahunAkademik::query()->withCount('semesters');

        if ($request->search) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $tahunAkademiks = $query->orderByDesc('tahun_mulai')
            ->paginate(10)
            ->withQueryString();

        // Get active tahun akademik
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();

        return Inertia::render('MasterData/TahunAkademik/Index', [
            'tahunAkademiks' => $tahunAkademiks,
            'activeTahunAkademik' => $activeTahunAkademik,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Store a newly created tahun akademik.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tahun_mulai' => ['required', 'integer', 'min:2000', 'max:2100'],
            'tahun_selesai' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:tahun_mulai'],
            'is_active' => ['boolean'],
        ]);

        // If setting as active, deactivate others
        if ($validated['is_active'] ?? false) {
            TahunAkademik::where('is_active', true)->update(['is_active' => false]);
        }

        $tahunAkademik = TahunAkademik::create($validated);

        // Auto create semesters (Ganjil & Genap)
        Semester::create([
            'tahun_akademik_id' => $tahunAkademik->id,
            'nama' => 'Ganjil',
            'tipe' => 'ganjil',
            'tanggal_mulai' => "{$validated['tahun_mulai']}-09-01",
            'tanggal_selesai' => "{$validated['tahun_mulai']}-12-31",
        ]);

        Semester::create([
            'tahun_akademik_id' => $tahunAkademik->id,
            'nama' => 'Genap',
            'tipe' => 'genap',
            'tanggal_mulai' => "{$validated['tahun_selesai']}-01-01",
            'tanggal_selesai' => "{$validated['tahun_selesai']}-06-30",
        ]);

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun Akademik berhasil ditambahkan.');
    }

    /**
     * Update the specified tahun akademik.
     */
    public function update(Request $request, TahunAkademik $tahunAkademik)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tahun_mulai' => ['required', 'integer', 'min:2000', 'max:2100'],
            'tahun_selesai' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:tahun_mulai'],
            'is_active' => ['boolean'],
        ]);

        // If setting as active, deactivate others
        if (($validated['is_active'] ?? false) && !$tahunAkademik->is_active) {
            TahunAkademik::where('is_active', true)->update(['is_active' => false]);
        }

        $tahunAkademik->update($validated);

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun Akademik berhasil diperbarui.');
    }

    /**
     * Remove the specified tahun akademik.
     */
    public function destroy(TahunAkademik $tahunAkademik)
    {
        // Check if has related data
        if ($tahunAkademik->semesters()->count() > 0) {
            return back()->with('error', 'Tidak dapat menghapus, masih ada semester terkait.');
        }

        $tahunAkademik->delete();

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun Akademik berhasil dihapus.');
    }

    /**
     * Set tahun akademik as active.
     */
    public function setActive(TahunAkademik $tahunAkademik)
    {
        TahunAkademik::where('is_active', true)->update(['is_active' => false]);
        $tahunAkademik->update(['is_active' => true]);

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun Akademik aktif berhasil diubah.');
    }
}
