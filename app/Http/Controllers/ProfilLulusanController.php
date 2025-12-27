<?php

namespace App\Http\Controllers;

use App\Models\Kurikulum;
use App\Models\ProfilLulusan;
use Illuminate\Http\Request;

class ProfilLulusanController extends Controller
{
    public function store(Request $request, Kurikulum $kurikulum)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
        ]);

        $pl = $kurikulum->profilLulusans()->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profil Lulusan berhasil ditambahkan.',
                'profil_lulusan' => $pl
            ]);
        }

        return back()->with('success', 'Profil Lulusan berhasil ditambahkan.');
    }

    public function update(Request $request, ProfilLulusan $profilLulusan)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
        ]);

        $profilLulusan->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profil Lulusan diperbarui.',
                'profil_lulusan' => $profilLulusan
            ]);
        }

        return back()->with('success', 'Profil Lulusan diperbarui.');
    }

    public function destroy(ProfilLulusan $profilLulusan)
    {
        $profilLulusan->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Profil Lulusan dihapus.']);
        }

        return back()->with('success', 'Profil Lulusan dihapus.');
    }

    public function updateMapping(Request $request, ProfilLulusan $profilLulusan)
    {
        $request->validate([
            'cpl_id' => 'required|exists:cpls,id',
            'skor' => 'nullable|numeric|min:0|max:100'
        ]);

        if ($request->skor === null || $request->skor == 0) {
            $profilLulusan->cpls()->detach($request->cpl_id);
        } else {
            $profilLulusan->cpls()->syncWithoutDetaching([$request->cpl_id => ['skor' => $request->skor]]);
        }

        return back()->with('success', 'Mapping diperbarui.');
    }
}
