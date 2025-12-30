<?php

namespace App\Http\Controllers;

use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class CpmkController extends Controller
{
    /**
     * Get CPMKs for a specific Mata Kuliah within a Kurikulum context.
     */
    public function getByKurikulumMataKuliah(Kurikulum $kurikulum, MataKuliah $mataKuliah)
    {
        $cpmks = Cpmk::where('mata_kuliah_id', $mataKuliah->id)
            ->whereHas('cpl', function ($q) use ($kurikulum) {
                $q->where('kurikulum_id', $kurikulum->id);
            })
            ->with(['cpl', 'subCpmks'])
            ->orderBy('urutan')
            ->orderBy('kode')
            ->get();

        return response()->json($cpmks);
    }

    /**
     * Store a new CPMK.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cpl_id' => 'required|exists:cpls,id',
            'mata_kuliah_id' => 'required|exists:mata_kuliahs,id',
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'bobot' => 'nullable|numeric|min:0',
            'urutan' => 'nullable|integer',
        ]);

        $cpmk = Cpmk::create($validated);

        return response()->json([
            'message' => 'CPMK berhasil ditambahkan.',
            'cpmk' => $cpmk->load('cpl'),
        ]);
    }

    /**
     * Update the specified CPMK.
     */
    public function update(Request $request, Cpmk $cpmk)
    {
        $validated = $request->validate([
            'cpl_id' => 'required|exists:cpls,id',
            'kode' => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'bobot' => 'nullable|numeric|min:0',
            'urutan' => 'nullable|integer',
        ]);

        $cpmk->update($validated);

        return response()->json([
            'message' => 'CPMK berhasil diperbarui.',
            'cpmk' => $cpmk->load('cpl'),
        ]);
    }

    /**
     * Remove the specified CPMK.
     */
    public function destroy(Cpmk $cpmk)
    {
        $cpmk->delete();

        return response()->json([
            'message' => 'CPMK berhasil dihapus.',
        ]);
    }
    /**
     * Generate CPMK using AI
     */
    public function generateAi(Kurikulum $kurikulum, MataKuliah $mataKuliah, \App\Services\AiService $aiService)
    {
        // 1. Gather Context
        // 1. Gather Context


        // Load assigned CPLs for this MK in this Kurikulum using direct pivot table query
        $assignedCplIds = DB::table('cpl_mata_kuliah')
            ->where('kurikulum_id', $kurikulum->id)
            ->where('mata_kuliah_id', $mataKuliah->id)
            ->pluck('cpl_id');

        $assignedCpls = \App\Models\Cpl::whereIn('id', $assignedCplIds)->get();

        if ($assignedCpls->isEmpty()) {
            // Fallback: Try to get all CPLs for this Kurikulum if none directly assigned (though frontend check prevents this)
            $assignedCpls = \App\Models\Cpl::where('kurikulum_id', $kurikulum->id)->take(3)->get();
        }

        $cplContext = $assignedCpls->map(fn($cpl) => "- {$cpl->kode}: {$cpl->deskripsi}")->join("\n");
        $mkContext = "Mata Kuliah: {$mataKuliah->nama} ({$mataKuliah->kode})\nSKS: {$mataKuliah->sks_total}\nDeskripsi: {$mataKuliah->deskripsi}";

        // 2. Construct Prompt
        $prompt = <<<EOT
You are an expert in Outcome-Based Education (OBE) curriculum design.
Generate between 3 to 6 CPMK (Capaian Pembelajaran Mata Kuliah) based on the complexity of the course context provided.
For each CPMK, generate 2-3 Sub-CPMK.
Based on the following context:

{$mkContext}

Mapped CPL (Capaian Pembelajaran Lulusan):
{$cplContext}

Rules:
1. Each CPMK must map to one of the provided CPL Codes.
2. Use specific active verbs (Bloom's Taxonomy).
3. Output strictly in JSON format.
4. Ensure the total 'bobot' across all generated CPMKs sums up to exactly 100. Distribute wisely based on complexity.

JSON Structure:
[
  {
    "kode": "CPMK-1",
    "deskripsi": "Description of CPMK...",
    "bobot": 15,
    "cpl_kode": "CPL-X", 
    "sub_cpmk": [
      {
        "kode": "Sub-CPMK-1.1",
        "deskripsi": "Description of Sub-CPMK..."
      }
    ]
  }
]
EOT;

        // 3. Call AI
        try {
            $jsonResponse = $aiService->generate($prompt);

            // Allow for markdown code block stripping
            $jsonResponse = str_replace(['```json', '```'], '', $jsonResponse);
            $data = json_decode($jsonResponse, true);

            if (!is_array($data)) {
                throw new \Exception('Invalid JSON from AI');
            }

            // 4. Save to Database
            $savedCpmks = [];

            \Illuminate\Support\Facades\DB::transaction(function () use ($data, $mataKuliah, $assignedCpls, &$savedCpmks) {
                foreach ($data as $idx => $item) {
                    // Match CPL
                    $cpl = $assignedCpls->firstWhere('kode', $item['cpl_kode']) ?? $assignedCpls->first();

                    if (!$cpl)
                        continue;

                    $cpmk = Cpmk::create([
                        'mata_kuliah_id' => $mataKuliah->id,
                        'cpl_id' => $cpl->id,
                        'kode' => $item['kode'],
                        'deskripsi' => $item['deskripsi'],
                        'bobot' => $item['bobot'] ?? 0,
                        'urutan' => $idx + 1,
                    ]);

                    // Save Sub-CPMKs
                    if (isset($item['sub_cpmk']) && is_array($item['sub_cpmk'])) {
                        foreach ($item['sub_cpmk'] as $sIdx => $subItem) {
                            \App\Models\SubCpmk::create([
                                'cpmk_id' => $cpmk->id,
                                'kode' => $subItem['kode'],
                                'deskripsi' => $subItem['deskripsi'],
                                'urutan' => $sIdx + 1,
                            ]);
                        }
                    }

                    $savedCpmks[] = $cpmk->load('cpl');
                }
            });

            return response()->json([
                'message' => 'CPMK successfully generated',
                'cpmks' => $savedCpmks
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'AI Generation Failed: ' . $e->getMessage()], 500);
        }
    }
}
