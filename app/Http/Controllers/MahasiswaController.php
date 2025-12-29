<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MahasiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Mahasiswa::with(['prodi', 'user', 'semesterMasuk']);
        $user = $request->user();

        // Scope for staff_prodi
        $userProdiIds = null;
        if ($user->hasRole('staff_prodi')) {
            $userProdiIds = $user->prodis()->pluck('program_studis.id');
            $query->whereIn('prodi_id', $userProdiIds);
        }

        // Filter by tab (trashed or active)
        if ($request->tab === 'trash') {
            $query->onlyTrashed();
        }

        // Filter by prodi
        if ($request->prodi_id) {
            $query->where('prodi_id', $request->prodi_id);
        }

        // Filter by angkatan
        if ($request->angkatan) {
            $query->where('angkatan', $request->angkatan);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $allSemesters = \App\Models\Semester::orderBy('tanggal_mulai')->get();
        $activeSemester = $allSemesters->firstWhere('is_active', true);

        // Filter by semester_ke
        if ($request->semester_ke && $activeSemester) {
            $idxActive = $allSemesters->search(fn($s) => $s->id == $activeSemester->id);
            // semester_ke = idxActive - idxEntry + 1
            // idxEntry = idxActive - semester_ke + 1
            $idxEntry = $idxActive - $request->semester_ke + 1;

            if ($idxEntry >= 0 && isset($allSemesters[$idxEntry])) {
                $targetEntryId = $allSemesters[$idxEntry]->id;
                $query->where('semester_masuk_id', $targetEntryId);
            } else {
                // Impossible semester, force empty
                $query->whereRaw('1 = 0');
            }
        }

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);

        $mahasiswas = $query->orderBy('nama')->paginate($perPage)->withQueryString()->through(function ($mhs) use ($allSemesters, $activeSemester) {
            $semesterKe = null;
            if ($mhs->semester_masuk_id && $activeSemester) {
                // Find index of entry semester and active semester
                $idxEntry = $allSemesters->search(fn($s) => $s->id == $mhs->semester_masuk_id);
                $idxActive = $allSemesters->search(fn($s) => $s->id == $activeSemester->id);

                if ($idxEntry !== false && $idxActive !== false && $idxActive >= $idxEntry) {
                    $semesterKe = $idxActive - $idxEntry + 1;
                }
            }
            $mhs->semester_ke = $semesterKe;
            return $mhs;
        });

        // Get unique angkatan for filter
        $angkatans = Mahasiswa::select('angkatan')
            ->distinct()
            ->whereNotNull('angkatan')
            ->orderByDesc('angkatan')
            ->pluck('angkatan');

        // Filter prodis list for dropdown
        $prodiQuery = ProgramStudi::orderBy('nama');
        if ($userProdiIds) {
            $prodiQuery->whereIn('id', $userProdiIds);
        }
        $prodisList = $prodiQuery->get();

        // Calculate stats with scope
        $statsQuery = Mahasiswa::query();
        $trashedQuery = Mahasiswa::onlyTrashed();

        if ($userProdiIds) {
            $statsQuery->whereIn('prodi_id', $userProdiIds);
            $trashedQuery->whereIn('prodi_id', $userProdiIds);
        }

        return Inertia::render('Mahasiswa/Index', [
            'mahasiswas' => $mahasiswas,
            'prodis' => $prodisList,
            'semesters' => \App\Models\Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get(),
            'angkatans' => $angkatans,
            'filters' => $request->only(['search', 'prodi_id', 'angkatan', 'status', 'tab', 'per_page', 'semester_ke']),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'aktif' => (clone $statsQuery)->where('status', 'aktif')->count(),
                'cuti' => (clone $statsQuery)->where('status', 'cuti')->count(),
                'lulus' => (clone $statsQuery)->where('status', 'lulus')->count(),
                'trashed' => $trashedQuery->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => 'required|exists:program_studis,id',
            'nim' => 'required|string|max:20|unique:mahasiswas,nim',
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:L,P',
            'semester_masuk_id' => 'required|exists:semesters,id',
            'status' => 'required|in:aktif,cuti,keluar,lulus',
        ]);

        // Sanitize input
        $validated['nama'] = strip_tags($validated['nama']);
        $validated['nim'] = strip_tags($validated['nim']);
        $validated['alamat'] = $validated['alamat'] ? strip_tags($validated['alamat']) : null;
        $validated['no_hp'] = $validated['no_hp'] ? strip_tags($validated['no_hp']) : null;

        $semester = \App\Models\Semester::with('tahunAkademik')->find($request->semester_masuk_id);
        $validated['semester_masuk_id'] = $semester->id;
        $validated['tahun_akademik_masuk_id'] = $semester->tahun_akademik_id;
        $validated['angkatan'] = \Carbon\Carbon::parse($semester->tahunAkademik->tanggal_mulai)->year;

        Mahasiswa::create($validated);

        return redirect()->back()->with('success', 'Mahasiswa berhasil ditambahkan');
    }

    public function update(Request $request, Mahasiswa $mahasiswa)
    {
        $validated = $request->validate([
            'prodi_id' => 'required|exists:program_studis,id',
            'nim' => 'required|string|max:20|unique:mahasiswas,nim,' . $mahasiswa->id,
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:L,P',
            'semester_masuk_id' => 'required|exists:semesters,id',
            'status' => 'required|in:aktif,cuti,keluar,lulus',
        ]);

        // Sanitize input
        $validated['nama'] = strip_tags($validated['nama']);
        $validated['nim'] = strip_tags($validated['nim']);
        $validated['alamat'] = $validated['alamat'] ? strip_tags($validated['alamat']) : null;
        $validated['no_hp'] = $validated['no_hp'] ? strip_tags($validated['no_hp']) : null;

        $semester = \App\Models\Semester::with('tahunAkademik')->find($request->semester_masuk_id);
        $validated['semester_masuk_id'] = $semester->id;
        $validated['tahun_akademik_masuk_id'] = $semester->tahun_akademik_id;
        $validated['angkatan'] = \Carbon\Carbon::parse($semester->tahunAkademik->tanggal_mulai)->year;

        $mahasiswa->update($validated);

        return redirect()->back()->with('success', 'Mahasiswa berhasil diperbarui');
    }

    public function destroy(Mahasiswa $mahasiswa)
    {
        // Deactivate user account if exists
        if ($mahasiswa->user_id) {
            User::where('id', $mahasiswa->user_id)->update(['is_active' => false]);
        }

        $mahasiswa->delete();
        return redirect()->back()->with('success', 'Mahasiswa berhasil dihapus' . ($mahasiswa->user_id ? ' dan akun user dinonaktifkan' : ''));
    }

    public function restore($id)
    {
        $mahasiswa = Mahasiswa::onlyTrashed()->findOrFail($id);
        $mahasiswa->restore();

        // Reactivate user account if exists
        if ($mahasiswa->user_id) {
            User::where('id', $mahasiswa->user_id)->update(['is_active' => true]);
        }

        return redirect()->back()->with('success', 'Mahasiswa berhasil dipulihkan' . ($mahasiswa->user_id ? ' dan akun diaktifkan kembali' : ''));
    }

    public function forceDelete($id)
    {
        $mahasiswa = Mahasiswa::onlyTrashed()->findOrFail($id);

        // Delete user account if exists
        if ($mahasiswa->user_id) {
            User::where('id', $mahasiswa->user_id)->forceDelete();
        }

        $mahasiswa->forceDelete();
        return redirect()->back()->with('success', 'Mahasiswa dan akun terkait berhasil dihapus permanen');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:mahasiswas,id',
        ]);

        // Deactivate related user accounts
        $mahasiswas = Mahasiswa::whereIn('id', $validated['ids'])->get();
        $userIds = $mahasiswas->pluck('user_id')->filter()->toArray();
        if (!empty($userIds)) {
            User::whereIn('id', $userIds)->update(['is_active' => false]);
        }

        Mahasiswa::whereIn('id', $validated['ids'])->delete();

        $accountsDeactivated = count($userIds);
        $message = count($validated['ids']) . ' mahasiswa berhasil dihapus';
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
        $mahasiswas = Mahasiswa::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $userIds = $mahasiswas->pluck('user_id')->filter()->toArray();
        if (!empty($userIds)) {
            User::whereIn('id', $userIds)->update(['is_active' => true]);
        }

        Mahasiswa::onlyTrashed()->whereIn('id', $validated['ids'])->restore();

        $accountsReactivated = count($userIds);
        $message = count($validated['ids']) . ' mahasiswa berhasil dipulihkan';
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
        $mahasiswas = Mahasiswa::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $userIds = $mahasiswas->pluck('user_id')->filter()->toArray();
        $accountsDeleted = 0;
        if (!empty($userIds)) {
            $accountsDeleted = User::whereIn('id', $userIds)->forceDelete();
        }

        Mahasiswa::onlyTrashed()->whereIn('id', $validated['ids'])->forceDelete();

        $message = count($validated['ids']) . ' mahasiswa berhasil dihapus permanen';
        if ($accountsDeleted > 0) {
            $message .= ", {$accountsDeleted} akun juga dihapus";
        }

        return redirect()->back()->with('success', $message);
    }

    public function createAccount(Mahasiswa $mahasiswa)
    {
        if ($mahasiswa->user_id) {
            return redirect()->back()->with('error', 'Mahasiswa sudah memiliki akun');
        }

        if (!$mahasiswa->email) {
            return redirect()->back()->with('error', 'Mahasiswa harus memiliki email untuk membuat akun');
        }

        // Check if email already exists
        if (User::where('email', $mahasiswa->email)->exists()) {
            return redirect()->back()->with('error', 'Email sudah digunakan oleh akun lain');
        }

        DB::transaction(function () use ($mahasiswa) {
            $user = User::create([
                'name' => $mahasiswa->nama,
                'email' => $mahasiswa->email,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]);

            $user->assignRole('mahasiswa');
            $mahasiswa->update(['user_id' => $user->id]);
        });

        return redirect()->back()->with('success', 'Akun mahasiswa berhasil dibuat. Password default: password123');
    }

    public function bulkCreateAccount(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:mahasiswas,id',
        ]);

        $created = 0;
        $skipped = 0;
        $errors = [];

        $mahasiswas = Mahasiswa::whereIn('id', $validated['ids'])->get();

        foreach ($mahasiswas as $mahasiswa) {
            if ($mahasiswa->user_id) {
                $skipped++;
                continue;
            }

            if (!$mahasiswa->email) {
                $errors[] = "{$mahasiswa->nama}: tidak ada email";
                $skipped++;
                continue;
            }

            if (User::where('email', $mahasiswa->email)->exists()) {
                $errors[] = "{$mahasiswa->nama}: email sudah digunakan";
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($mahasiswa, &$created) {
                $user = User::create([
                    'name' => $mahasiswa->nama,
                    'email' => $mahasiswa->email,
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                ]);

                $user->assignRole('mahasiswa');
                $mahasiswa->update(['user_id' => $user->id]);
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
            'semester_masuk_id' => 'nullable|exists:semesters,id',
        ]);

        try {
            $file = $request->file('file');
            $path = $file->getRealPath();
            $prodiId = $request->prodi_id ?: null;
            $semesterMasukId = $request->semester_masuk_id ?: null;

            $user = $request->user();
            $allowedProdiIds = null;
            if ($user->hasRole('staff_prodi')) {
                $allowedProdiIds = $user->prodis()->pluck('program_studis.id')->toArray();
                if ($prodiId && !in_array((int) $prodiId, $allowedProdiIds)) {
                    throw new \Exception('Anda tidak memiliki akses ke Prodi yang dipilih.');
                }
            }

            $importService = new \App\Services\MahasiswaImportService();
            $result = $importService->import($path, $prodiId, $semesterMasukId, $allowedProdiIds);

            $message = "Import selesai: {$result['imported']} mahasiswa berhasil diimport";
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

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Nama', 'NIM', 'Email', 'No HP', 'Alamat', 'Tanggal Lahir', 'Jenis Kelamin', 'Angkatan', 'Status'];
        $sheet->fromArray($headers, null, 'A1');

        // Sample data
        $sample = ['Budi Santoso', '2025001', 'budi@student.uika.ac.id', '081234567890', 'Jl. Sholeh Iskandar No. 1', '2000-01-01', 'L', date('Y'), 'aktif'];
        $sheet->fromArray($sample, null, 'A2');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Auto width
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_mahasiswa.xlsx';
        $tempPath = storage_path('app/temp/' . $filename);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
    public function bulkUpdateSemester(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:mahasiswas,id',
            'semester_masuk_id' => 'required|exists:semesters,id',
        ]);

        $semester = \App\Models\Semester::with('tahunAkademik')->find($request->semester_masuk_id);
        $updateData = [
            'semester_masuk_id' => $semester->id,
            'tahun_akademik_masuk_id' => $semester->tahun_akademik_id,
            'angkatan' => \Carbon\Carbon::parse($semester->tahunAkademik->tanggal_mulai)->year,
        ];

        Mahasiswa::whereIn('id', $validated['ids'])->update($updateData);

        return redirect()->back()->with('success', count($validated['ids']) . ' mahasiswa berhasil diperbarui semester masuknya');
    }
}
