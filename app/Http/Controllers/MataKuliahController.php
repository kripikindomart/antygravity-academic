<?php

namespace App\Http\Controllers;

use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MataKuliahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MataKuliah::with(['prodi', 'prasyarat'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('nama_en', 'like', "%{$search}%");
                });
            })
            ->when($request->prodi_id, function ($query, $prodiId) {
                $query->where('prodi_id', $prodiId);
            })
            ->when($request->semester, function ($query, $semester) {
                $query->where('semester', $semester);
            })
            ->when($request->jenis, function ($query, $jenis) {
                $query->where('jenis', $jenis);
            });

        if ($request->has('trash') && $request->trash) {
            $query->onlyTrashed();
        }

        // Add filter for status if needed, defaults to all if not specified, 
        // but schema has is_active default true.
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        $mataKuliahs = $query->orderBy('prodi_id')
            ->orderBy('semester')
            ->orderBy('kode')
            ->paginate(10)
            ->withQueryString();

        $prodis = ProgramStudi::where('is_active', true)->orderBy('nama')->get();

        // For prasyarat dropdown, we might want all active MKs. 
        // Ideally filter by selected Prodi in frontend, but passing all here for simplicity initially
        // or fetch via API if too many. Let's pass simple all active list (id, kode, nama).
        $allMataKuliahs = MataKuliah::where('is_active', true)
            ->select('id', 'kode', 'nama', 'prodi_id')
            ->get();

        // Get user's bound prodi (for staff_prodi role)
        $userProdiId = null;
        $user = $request->user();
        if ($user && $user->prodis()->count() === 1) {
            $userProdiId = $user->prodis()->first()->id;
        }

        return Inertia::render('MasterData/MataKuliah/Index', [
            'mataKuliahs' => $mataKuliahs,
            'prodis' => $prodis,
            'allMataKuliahs' => $allMataKuliahs,
            'filters' => $request->only(['search', 'prodi_id', 'semester', 'jenis', 'status', 'trash']),
            'userProdiId' => $userProdiId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', 'unique:mata_kuliahs,kode'],
            'nama' => ['required', 'string', 'max:200'],
            'nama_en' => ['nullable', 'string', 'max:200'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'semester' => ['required', 'integer', 'min:1'],
            'jenis' => ['required', 'in:wajib,pilihan'],
            'deskripsi' => ['nullable', 'string'],
            'prasyarat_id' => ['nullable', 'exists:mata_kuliahs,id'],
            'is_active' => ['boolean'],
        ]);

        if (isset($validated['prasyarat_id']) && $validated['kode'] === $request->input('prasyarat_kode')) {
            // Logic validation on self-reference is handled by ID usually. 
            // We can't check ID self-ref on create.
        }

        MataKuliah::create($validated);

        return redirect()->route('mata-kuliah.index')
            ->with('success', 'Mata Kuliah berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MataKuliah $mataKuliah)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:program_studis,id'],
            'kode' => ['required', 'string', 'max:20', "unique:mata_kuliahs,kode,{$mataKuliah->id}"],
            'nama' => ['required', 'string', 'max:200'],
            'nama_en' => ['nullable', 'string', 'max:200'],
            'sks_teori' => ['required', 'integer', 'min:0'],
            'sks_praktik' => ['required', 'integer', 'min:0'],
            'semester' => ['required', 'integer', 'min:1'],
            'jenis' => ['required', 'in:wajib,pilihan'],
            'deskripsi' => ['nullable', 'string'],
            'prasyarat_id' => ['nullable', 'exists:mata_kuliahs,id', "not_in:{$mataKuliah->id}"],
            'is_active' => ['boolean'],
        ]);

        $mataKuliah->update($validated);

        return redirect()->route('mata-kuliah.index')
            ->with('success', 'Mata Kuliah berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MataKuliah $mataKuliah)
    {
        $mataKuliah->delete();

        return redirect()->route('mata-kuliah.index')
            ->with('success', 'Mata Kuliah berhasil dihapus.');
    }

    /**
     * Restore trashed item
     */
    public function restore($id)
    {
        $mataKuliah = MataKuliah::withTrashed()->findOrFail($id);
        $mataKuliah->restore();

        return redirect()->route('mata-kuliah.index', ['trash' => 1])
            ->with('success', 'Mata Kuliah berhasil dipulihkan.');
    }

    /**
     * Force delete item
     */
    public function forceDelete($id)
    {
        $mataKuliah = MataKuliah::withTrashed()->findOrFail($id);
        $mataKuliah->forceDelete();

        return redirect()->route('mata-kuliah.index', ['trash' => 1])
            ->with('success', 'Mata Kuliah berhasil dihapus permanen.');
    }

    /**
     * Import from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
            'prodi_id' => ['required', 'exists:program_studis,id'],
        ]);

        $file = $request->file('file');
        $prodiId = $request->prodi_id;

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $imported = 0;
            $skipped = 0;

            // Skip header row
            foreach (array_slice($rows, 1) as $row) {
                $kode = trim($row[0] ?? '');
                $nama = trim($row[1] ?? '');
                $namaEn = trim($row[2] ?? '');
                $sksTeori = (int) ($row[3] ?? 0);
                $sksPraktik = (int) ($row[4] ?? 0);
                $semester = (int) ($row[5] ?? 1);
                $jenis = strtolower(trim($row[6] ?? 'wajib'));

                if (empty($kode) || empty($nama)) {
                    $skipped++;
                    continue;
                }

                // Check duplicate (including trashed)
                if (MataKuliah::withTrashed()->where('kode', $kode)->exists()) {
                    $skipped++;
                    continue;
                }

                MataKuliah::create([
                    'prodi_id' => $prodiId,
                    'kode' => $kode,
                    'nama' => $nama,
                    'nama_en' => $namaEn ?: null,
                    'sks_teori' => $sksTeori,
                    'sks_praktik' => $sksPraktik,
                    'semester' => max(1, min(8, $semester)),
                    'jenis' => in_array($jenis, ['wajib', 'pilihan']) ? $jenis : 'wajib',
                    'is_active' => true,
                ]);
                $imported++;
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "{$imported} Mata Kuliah berhasil diimport. {$skipped} dilewati (duplikat/kosong).",
                    'success' => true,
                ]);
            }

            return back()->with('success', "{$imported} Mata Kuliah berhasil diimport. {$skipped} dilewati (duplikat/kosong).");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Gagal import: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['Kode MK', 'Nama (Indonesia)', 'Nama (Inggris)', 'SKS Teori', 'SKS Praktik', 'Semester', 'Jenis (wajib/pilihan)'];
        $sheet->fromArray($headers, null, 'A1');

        // Style header
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
        ]);

        // Sample data
        $sample = [
            ['MK001', 'Metodologi Penelitian', 'Research Methodology', 2, 0, 1, 'wajib'],
            ['MK002', 'Statistika Lanjut', 'Advanced Statistics', 3, 0, 1, 'wajib'],
        ];
        $sheet->fromArray($sample, null, 'A2');

        // Auto width
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_mata_kuliah.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Bulk delete / Force Delete
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'], // Can be generic integer validation as we check db below
        ]);

        $ids = $validated['ids'];

        if ($request->force) {
            $count = MataKuliah::onlyTrashed()->whereIn('id', $ids)->forceDelete();
            $msg = "{$count} Mata Kuliah berhasil dihapus permanen.";
        } else {
            $count = MataKuliah::whereIn('id', $ids)->delete();
            $msg = "{$count} Mata Kuliah berhasil dihapus.";
        }

        return back()->with('success', $msg);
    }

    /**
     * Bulk Restore
     */
    public function bulkRestore(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = MataKuliah::onlyTrashed()->whereIn('id', $validated['ids'])->restore();

        return back()->with('success', "{$count} Mata Kuliah berhasil dipulihkan.");
    }
}

