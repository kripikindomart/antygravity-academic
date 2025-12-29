<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DosenController extends Controller
{
    public function index(Request $request)
    {
        $query = Dosen::with(['prodi', 'user']);

        // Filter by tab (trashed or active)
        if ($request->tab === 'trash') {
            $query->onlyTrashed();
        }

        // Filter by prodi
        if ($request->prodi_id) {
            $query->where('prodi_id', $request->prodi_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nidn', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $dosens = $query->orderBy('nama')->paginate($perPage)->withQueryString();

        return Inertia::render('Dosen/Index', [
            'dosens' => $dosens,
            'prodis' => ProgramStudi::orderBy('nama')->get(),
            'filters' => $request->only(['search', 'prodi_id', 'status', 'tab', 'per_page']),
            'stats' => [
                'total' => Dosen::count(),
                'aktif' => Dosen::where('status', 'aktif')->count(),
                'nonaktif' => Dosen::where('status', 'nonaktif')->count(),
                'trashed' => Dosen::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => 'nullable|exists:program_studis,id',
            'nip' => 'nullable|string|max:50|unique:dosens,nip',
            'nidn' => 'nullable|string|max:20|unique:dosens,nidn',
            'nama' => 'required|string|max:255',
            'gelar_depan' => 'nullable|string|max:50',
            'gelar_belakang' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:20',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'jabatan_fungsional' => 'nullable|string|max:100',
            'jabatan_struktural' => 'nullable|string|max:100',
            'pangkat_golongan' => 'nullable|string|max:50',
            'bidang_keahlian' => 'nullable|string|max:255',
            'pendidikan_terakhir' => 'nullable|string|max:50',
            'is_dosen_luar' => 'boolean',
            'status' => 'required|in:aktif,nonaktif,cuti',
        ]);

        Dosen::create($validated);

        return redirect()->back()->with('success', 'Dosen berhasil ditambahkan');
    }

    public function update(Request $request, Dosen $dosen)
    {
        $validated = $request->validate([
            'prodi_id' => 'nullable|exists:program_studis,id',
            'nip' => 'nullable|string|max:50|unique:dosens,nip,' . $dosen->id,
            'nidn' => 'nullable|string|max:20|unique:dosens,nidn,' . $dosen->id,
            'nama' => 'required|string|max:255',
            'gelar_depan' => 'nullable|string|max:50',
            'gelar_belakang' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:20',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'jabatan_fungsional' => 'nullable|string|max:100',
            'jabatan_struktural' => 'nullable|string|max:100',
            'pangkat_golongan' => 'nullable|string|max:50',
            'bidang_keahlian' => 'nullable|string|max:255',
            'pendidikan_terakhir' => 'nullable|string|max:50',
            'is_dosen_luar' => 'boolean',
            'status' => 'required|in:aktif,nonaktif,cuti',
        ]);

        $dosen->update($validated);

        return redirect()->back()->with('success', 'Dosen berhasil diperbarui');
    }

    public function destroy(Dosen $dosen)
    {
        // Deactivate user account if exists
        if ($dosen->user_id) {
            User::where('id', $dosen->user_id)->update(['is_active' => false]);
        }

        $dosen->delete();
        return redirect()->back()->with('success', 'Dosen berhasil dihapus' . ($dosen->user_id ? ' dan akun dinonaktifkan' : ''));
    }

    public function restore($id)
    {
        $dosen = Dosen::onlyTrashed()->findOrFail($id);

        // Reactivate user account if exists
        if ($dosen->user_id) {
            User::where('id', $dosen->user_id)->update(['is_active' => true]);
        }

        $dosen->restore();
        return redirect()->back()->with('success', 'Dosen berhasil dipulihkan' . ($dosen->user_id ? ' dan akun diaktifkan kembali' : ''));
    }

    public function forceDelete($id)
    {
        $dosen = Dosen::onlyTrashed()->findOrFail($id);

        // Delete user account if exists
        if ($dosen->user_id) {
            User::where('id', $dosen->user_id)->forceDelete();
        }

        $dosen->forceDelete();
        return redirect()->back()->with('success', 'Dosen dan akun terkait berhasil dihapus permanen');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:dosens,id',
        ]);

        // Deactivate related user accounts
        $dosens = Dosen::whereIn('id', $validated['ids'])->get();
        $userIds = $dosens->pluck('user_id')->filter()->toArray();
        if (!empty($userIds)) {
            User::whereIn('id', $userIds)->update(['is_active' => false]);
        }

        Dosen::whereIn('id', $validated['ids'])->delete();

        $accountsDeactivated = count($userIds);
        $message = count($validated['ids']) . ' dosen berhasil dihapus';
        if ($accountsDeactivated > 0) {
            $message .= ", {$accountsDeactivated} akun dinonaktifkan";
        }

        return redirect()->back()->with('success', $message);
    }

    public function bulkRestore(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        // Reactivate related user accounts
        $dosens = Dosen::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $userIds = $dosens->pluck('user_id')->filter()->toArray();
        if (!empty($userIds)) {
            User::whereIn('id', $userIds)->update(['is_active' => true]);
        }

        Dosen::onlyTrashed()->whereIn('id', $validated['ids'])->restore();

        $accountsReactivated = count($userIds);
        $message = count($validated['ids']) . ' dosen berhasil dipulihkan';
        if ($accountsReactivated > 0) {
            $message .= ", {$accountsReactivated} akun diaktifkan kembali";
        }

        return redirect()->back()->with('success', $message);
    }

    public function bulkForceDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        // Delete related user accounts
        $dosens = Dosen::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $userIds = $dosens->pluck('user_id')->filter()->toArray();
        $accountsDeleted = 0;
        if (!empty($userIds)) {
            $accountsDeleted = User::whereIn('id', $userIds)->forceDelete();
        }

        Dosen::onlyTrashed()->whereIn('id', $validated['ids'])->forceDelete();

        $message = count($validated['ids']) . ' dosen berhasil dihapus permanen';
        if ($accountsDeleted > 0) {
            $message .= ", {$accountsDeleted} akun juga dihapus";
        }

        return redirect()->back()->with('success', $message);
    }

    public function createAccount(Dosen $dosen)
    {
        if ($dosen->user_id) {
            return redirect()->back()->with('error', 'Dosen sudah memiliki akun');
        }

        if (!$dosen->email) {
            return redirect()->back()->with('error', 'Dosen harus memiliki email untuk membuat akun');
        }

        // Check if email already exists in users
        if (User::where('email', $dosen->email)->exists()) {
            return redirect()->back()->with('error', 'Email sudah digunakan oleh akun lain');
        }

        DB::transaction(function () use ($dosen) {
            $user = User::create([
                'name' => $dosen->nama,
                'email' => $dosen->email,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]);

            $user->assignRole('dosen');
            $dosen->update(['user_id' => $user->id]);
        });

        return redirect()->back()->with('success', 'Akun dosen berhasil dibuat. Password default: password123');
    }

    public function bulkCreateAccount(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:dosens,id',
        ]);

        $created = 0;
        $skipped = 0;
        $errors = [];

        $dosens = Dosen::whereIn('id', $validated['ids'])->get();

        foreach ($dosens as $dosen) {
            if ($dosen->user_id) {
                $skipped++;
                continue;
            }

            if (!$dosen->email) {
                $errors[] = "{$dosen->nama}: tidak ada email";
                $skipped++;
                continue;
            }

            if (User::where('email', $dosen->email)->exists()) {
                $errors[] = "{$dosen->nama}: email sudah digunakan";
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($dosen, &$created) {
                $user = User::create([
                    'name' => $dosen->nama,
                    'email' => $dosen->email,
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                ]);

                $user->assignRole('dosen');
                $dosen->update(['user_id' => $user->id]);
                $created++;
            });
        }

        $message = "{$created} akun berhasil dibuat";
        if ($skipped > 0) {
            $message .= ", {$skipped} dilewati";
        }

        if (!empty($errors)) {
            return redirect()->back()
                ->with('warning', $message)
                ->with('bulk_errors', $errors);
        }

        return redirect()->back()->with('success', $message . '. Password default: password123');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'prodi_id' => 'nullable|exists:program_studis,id',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            $prodiId = $request->prodi_id ?: null;

            $importService = new \App\Services\DosenImportService();
            $result = $importService->import($path, $prodiId);

            $message = "Import selesai: {$result['imported']} dosen berhasil diimport";
            if ($result['skipped'] > 0) {
                $message .= ", {$result['skipped']} dilewati";
            }

            if (!empty($result['errors'])) {
                return redirect()->back()
                    ->with('warning', $message)
                    ->with('import_errors', $result['errors']);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel for import
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Nama', 'NIP', 'NIDN', 'Email', 'Telepon', 'Gelar Depan', 'Gelar Belakang', 'Jenis Kelamin', 'Jabatan Fungsional', 'Pendidikan Terakhir', 'Bidang Keahlian', 'Status'];
        $sheet->fromArray($headers, null, 'A1');

        // Sample data
        $sample = ['Dr. Ahmad Maulana', '197501012000121001', '0015017502', 'ahmad@uika.ac.id', '081234567890', 'Dr.', 'M.Kom', 'L', 'Lektor', 'S3', 'Sistem Informasi', 'aktif'];
        $sheet->fromArray($sample, null, 'A2');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Auto width
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_dosen.xlsx';
        $tempPath = storage_path('app/temp/' . $filename);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
