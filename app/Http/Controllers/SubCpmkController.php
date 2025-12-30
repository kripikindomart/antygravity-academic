<?php

namespace App\Http\Controllers;

use App\Models\SubCpmk;
use Illuminate\Http\Request;

class SubCpmkController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cpmk_id' => 'required|exists:cpmks,id',
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'urutan' => 'integer|nullable',
        ]);

        $subCpmk = SubCpmk::create($validated);

        if ($request->wantsJson()) {
            // Return the sub_cpmk data directly (not nested) for easy frontend use
            return response()->json($subCpmk->load('cpmk'));
        }

        return back()->with('success', 'Sub-CPMK berhasil dibuat.');
    }

    public function update(Request $request, SubCpmk $subCpmk)
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'urutan' => 'integer|nullable',
        ]);

        $subCpmk->update($validated);

        if ($request->wantsJson()) {
            return response()->json($subCpmk->load('cpmk'));
        }
        return back()->with('success', 'Sub-CPMK updated.');
    }

    public function destroy(Request $request, SubCpmk $subCpmk)
    {
        $subCpmk->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Sub-CPMK dihapus.');
    }
}
