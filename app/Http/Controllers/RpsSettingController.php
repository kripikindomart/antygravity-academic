<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Models\Dosen;
use App\Models\JabatanStruktural;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RpsSettingController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $prodiIds = null;

        if ($user->hasRole('staff_prodi')) {
            $prodiIds = $user->prodis()->pluck('program_studis.id')->toArray();
        }

        $query = ProgramStudi::query()
            ->with([
                'kaprodi.dosen',
                'dosens', // For selection list
            ]);

        // Also load Koordinator RMK
        // Since 'koordinator_rmk' is not a standard relationship in model yet, we can load it via jabatanStruktural
        $query->with([
            'jabatanStruktural' => function ($q) {
                $q->whereIn('jabatan', ['kaprodi', 'koordinator_rmk'])
                    ->where('is_active', true)
                    ->with('dosen');
            }
        ]);

        if ($prodiIds) {
            $query->whereIn('id', $prodiIds);
        }

        $prodis = $query->orderBy('nama')->get()->map(function ($prodi) {
            // Map structured data for frontend
            $kaprodi = $prodi->jabatanStruktural->firstWhere('jabatan', 'kaprodi');
            $koordinatorRmk = $prodi->jabatanStruktural->firstWhere('jabatan', 'koordinator_rmk');

            return [
                'id' => $prodi->id,
                'nama' => $prodi->nama,
                'kode' => $prodi->kode,
                'kaprodi_id' => $kaprodi ? $kaprodi->dosen_id : null,
                'kaprodi_name' => $kaprodi && $kaprodi->dosen ? $kaprodi->dosen->name : null,
                'koordinator_rmk_id' => $koordinatorRmk ? $koordinatorRmk->dosen_id : null,
                'koordinator_rmk_name' => $koordinatorRmk && $koordinatorRmk->dosen ? $koordinatorRmk->dosen->name : null,
                // List of available dosens in this prodi
                'dosens' => $prodi->dosens->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'nidn' => $d->nidn]),
            ];
        });

        // Global list of dosens might be needed if cross-prodi assignment is allowed
        // But usually it's internal. Keeping it strict to prodi->dosens for now.

        return Inertia::render('Rps/Settings', [
            'prodis' => $prodis
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => 'required|exists:program_studis,id',
            'kaprodi_id' => 'nullable|exists:dosens,id',
            'koordinator_rmk_id' => 'nullable|exists:dosens,id',
        ]);

        $prodiId = $validated['prodi_id'];

        // Access check
        if (auth()->user()->hasRole('staff_prodi')) {
            if (!auth()->user()->prodis->contains($prodiId)) {
                abort(403);
            }
        }

        // Update Kaprodi
        $this->updateJabatan($prodiId, 'kaprodi', $validated['kaprodi_id']);

        // Update Koordinator RMK
        $this->updateJabatan($prodiId, 'koordinator_rmk', $validated['koordinator_rmk_id']);

        return back()->with('success', 'Pengaturan Struktural Prodi berhasil diperbarui');
    }

    private function updateJabatan($prodiId, $jabatan, $dosenId)
    {
        if (!$dosenId) {
            // Nullify or deactivate?
            // If null is sent, meaning remove assignment.
            JabatanStruktural::where('program_studi_id', $prodiId)
                ->where('jabatan', $jabatan)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            return;
        }

        // Check if same dosen already active
        $current = JabatanStruktural::where('program_studi_id', $prodiId)
            ->where('jabatan', $jabatan)
            ->where('is_active', true)
            ->first();

        if ($current && $current->dosen_id == $dosenId) {
            return; // No change
        }

        // Deactivate old
        if ($current) {
            $current->update(['is_active' => false, 'tanggal_selesai' => now()]);
        }

        // Create new
        JabatanStruktural::create([
            'program_studi_id' => $prodiId,
            'dosen_id' => $dosenId,
            'jabatan' => $jabatan,
            'tanggal_mulai' => now(),
            'is_active' => true,
        ]);
    }
}
