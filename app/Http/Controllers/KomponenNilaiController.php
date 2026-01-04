<?php

namespace App\Http\Controllers;

use App\Models\KomponenNilai;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KomponenNilaiController extends Controller
{
    public function index(Request $request)
    {
        // Get Global komponen stats
        $globalStats = KomponenNilai::whereNull('prodi_id')
            ->where('is_active', true)
            ->selectRaw('COALESCE(SUM(bobot), 0) as total_bobot, COUNT(*) as components_count')
            ->first();

        $query = ProgramStudi::withCount([
            'komponenNilais as total_bobot' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(bobot), 0)'));
            }
        ])->withCount('komponenNilais as components_count');

        if ($request->search) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        $items = $query->orderBy('nama')->paginate(10)->through(function ($prodi) {
            return [
                'id' => $prodi->id,
                'nama' => $prodi->nama,
                'jenjang' => $prodi->jenjang,
                'total_bobot' => (float) $prodi->total_bobot,
                'components_count' => $prodi->components_count,
                'is_global' => false,
            ];
        });

        return Inertia::render('Akademik/KomponenNilai/Index', [
            'items' => $items,
            'globalStats' => [
                'total_bobot' => (float) ($globalStats->total_bobot ?? 0),
                'components_count' => (int) ($globalStats->components_count ?? 0),
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    public function editGlobal()
    {
        $komponens = KomponenNilai::whereNull('prodi_id')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Akademik/KomponenNilai/Edit', [
            'prodi' => null, // null means global
            'komponens' => $komponens,
        ]);
    }

    public function updateGlobal(Request $request)
    {
        $request->validate([
            'components' => 'present|array',
            'components.*.nama' => 'required|string',
            'components.*.bobot' => 'required|numeric|min:0|max:100',
        ]);

        $totalBobot = collect($request->components)->sum('bobot');

        if (abs($totalBobot - 100) > 0.1) {
            return redirect()->back()->withErrors(['total_bobot' => 'Total bobot harus 100%. Saat ini: ' . $totalBobot . '%']);
        }

        DB::transaction(function () use ($request) {
            // Delete existing global
            KomponenNilai::whereNull('prodi_id')->delete();

            foreach ($request->components as $comp) {
                KomponenNilai::create([
                    'prodi_id' => null, // Global
                    'nama' => $comp['nama'],
                    'bobot' => $comp['bobot'],
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('komponen-nilai.index')->with('success', 'Komponen Nilai Global berhasil disimpan.');
    }

    public function edit(ProgramStudi $prodi)
    {
        $komponens = KomponenNilai::where('prodi_id', $prodi->id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Akademik/KomponenNilai/Edit', [
            'prodi' => $prodi,
            'komponens' => $komponens,
        ]);
    }

    public function update(Request $request, ProgramStudi $prodi)
    {
        $request->validate([
            'components' => 'present|array',
            'components.*.nama' => 'required|string',
            'components.*.bobot' => 'required|numeric|min:0|max:100',
        ]);

        $totalBobot = collect($request->components)->sum('bobot');

        if (abs($totalBobot - 100) > 0.1) {
            return redirect()->back()->withErrors(['total_bobot' => 'Total bobot harus 100%. Saat ini: ' . $totalBobot . '%']);
        }

        DB::transaction(function () use ($prodi, $request) {
            // Delete existing
            KomponenNilai::where('prodi_id', $prodi->id)->delete();

            foreach ($request->components as $comp) {
                KomponenNilai::create([
                    'prodi_id' => $prodi->id,
                    'nama' => $comp['nama'],
                    'bobot' => $comp['bobot'],
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('komponen-nilai.index')->with('success', 'Komponen Nilai untuk ' . $prodi->nama . ' berhasil disimpan.');
    }
}
