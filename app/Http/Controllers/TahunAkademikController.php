<?php

namespace App\Http\Controllers;

use App\Models\TahunAkademik;
use App\Models\Semester;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class TahunAkademikController extends Controller
{
    /**
     * Display a listing of tahun akademik.
     */
    public function index(Request $request)
    {
        $query = TahunAkademik::query()
            ->with([
                'semesters' => function ($q) {
                    $q->orderBy('tipe', 'asc'); // Ganjil then Genap
                }
            ])
            ->withCount('semesters');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                    ->orWhere('kode', 'like', "%{$request->search}%");
            });
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $tahunAkademiks = $query->orderByDesc('tanggal_mulai')
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
            'kode' => ['required', 'string', 'max:20', 'unique:tahun_akademiks,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'ganjil_mulai' => ['required', 'date'],
            'ganjil_selesai' => ['required', 'date', 'after:ganjil_mulai'],
            'genap_mulai' => ['required', 'date', 'after:ganjil_selesai'],
            'genap_selesai' => ['required', 'date', 'after:genap_mulai'],
            'is_active' => ['boolean'],
        ]);

        // If setting as active, deactivate others
        if ($validated['is_active'] ?? false) {
            TahunAkademik::where('is_active', true)->update(['is_active' => false]);
        }

        // Initialize transaction
        \DB::transaction(function () use ($validated) {
            // Create Tahun Akademik (Start = Ganjil Start, End = Genap End)
            $tahunAkademik = TahunAkademik::create([
                'kode' => $validated['kode'],
                'nama' => $validated['nama'],
                'tanggal_mulai' => $validated['ganjil_mulai'],
                'tanggal_selesai' => $validated['genap_selesai'],
                'is_active' => $validated['is_active'] ?? false,
            ]);

            $tahunMulai = Carbon::parse($validated['ganjil_mulai'])->year;

            // Create Semester Ganjil
            Semester::create([
                'tahun_akademik_id' => $tahunAkademik->id,
                'kode' => "{$tahunMulai}1",
                'nama' => 'Ganjil',
                'tipe' => 'ganjil',
                'tanggal_mulai' => $validated['ganjil_mulai'],
                'tanggal_selesai' => $validated['ganjil_selesai'],
            ]);

            // Create Semester Genap
            Semester::create([
                'tahun_akademik_id' => $tahunAkademik->id,
                'kode' => "{$tahunMulai}2",
                'nama' => 'Genap',
                'tipe' => 'genap',
                'tanggal_mulai' => $validated['genap_mulai'],
                'tanggal_selesai' => $validated['genap_selesai'],
            ]);
        });

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun Akademik berhasil ditambahkan.');
    }

    /**
     * Update the specified tahun akademik.
     */
    public function update(Request $request, TahunAkademik $tahunAkademik)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', "unique:tahun_akademiks,kode,{$tahunAkademik->id}"],
            'nama' => ['required', 'string', 'max:255'],
            'ganjil_mulai' => ['required', 'date'],
            'ganjil_selesai' => ['required', 'date', 'after:ganjil_mulai'],
            'genap_mulai' => ['required', 'date', 'after:ganjil_selesai'],
            'genap_selesai' => ['required', 'date', 'after:genap_mulai'],
            'is_active' => ['boolean'],
        ]);

        if (($validated['is_active'] ?? false) && !$tahunAkademik->is_active) {
            TahunAkademik::where('is_active', true)->update(['is_active' => false]);
        }

        \DB::transaction(function () use ($validated, $tahunAkademik) {
            $tahunAkademik->update([
                'kode' => $validated['kode'],
                'nama' => $validated['nama'],
                'tanggal_mulai' => $validated['ganjil_mulai'],
                'tanggal_selesai' => $validated['genap_selesai'],
                'is_active' => $validated['is_active'] ?? false,
            ]);

            // Update Semesters
            // Assuming Semesters already exist correctly ordered or by type
            $tahunAkademik->semesters()->where('tipe', 'ganjil')->update([
                'tanggal_mulai' => $validated['ganjil_mulai'],
                'tanggal_selesai' => $validated['ganjil_selesai'],
            ]);

            $tahunAkademik->semesters()->where('tipe', 'genap')->update([
                'tanggal_mulai' => $validated['genap_mulai'],
                'tanggal_selesai' => $validated['genap_selesai'],
            ]);
        });

        return redirect()->route('tahun-akademik.index')
            ->with('success', 'Tahun Akademik berhasil diperbarui.');
    }

    /**
     * Remove the specified tahun akademik.
     */
    public function destroy(TahunAkademik $tahunAkademik)
    {
        if ($tahunAkademik->semesters()->count() > 0) {
            // Allow force delete or cascade? 
            // Since semesters are child of TA, they should cascade implicitly in logic or explicitly here.
            // But usually we don't want to delete TA if used.
            // However, since we auto-created them, we should allow deleting them ALL if it's clean.
            // For now, let's allow delete if NO registered classes/schedule (which we don't check yet).
            // But migration has cascadeOnDelete.
            $tahunAkademik->delete();
            return redirect()->route('tahun-akademik.index')->with('success', 'Tahun Akademik berhasil dihapus.');
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
