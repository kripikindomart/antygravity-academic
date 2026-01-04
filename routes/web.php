<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TahunAkademikController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\RuanganController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect('/login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Public routes (no auth required)
Route::get('/verify/{code}', [\App\Http\Controllers\RpsVerificationController::class, 'verify'])->name('rps.verify');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Jadwal Kuliah (Calendar view)
    Route::get('/jadwal', [\App\Http\Controllers\JadwalKuliahController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal', [\App\Http\Controllers\JadwalKuliahController::class, 'store'])->name('jadwal.store')->middleware('permission:jadwal.create');

    // Profile
    Route::prefix('profile')->name('profile.')->middleware('auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [\App\Http\Controllers\ProfileController::class, 'update'])->name('update');
        Route::put('/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password');
    });

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:users.view');
        Route::post('/export', [UserController::class, 'export'])->name('export')->middleware('permission:users.view');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');
        Route::post('/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('permission:users.delete');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:users.edit');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password')->middleware('permission:users.edit');

        // Trash routes
        Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore')->middleware('permission:users.delete');
        Route::delete('/{id}/force-delete', [UserController::class, 'forceDelete'])->name('force-delete')->middleware('permission:users.delete');
        Route::post('/bulk-restore', [UserController::class, 'bulkRestore'])->name('bulk-restore')->middleware('permission:users.delete');
        Route::post('/bulk-force-delete', [UserController::class, 'bulkForceDelete'])->name('bulk-force-delete')->middleware('permission:users.delete');
    });

    // Role Management
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('permission:roles.view');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('permission:roles.create');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:roles.create');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('permission:roles.edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('permission:roles.edit');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('permission:roles.delete');
    });

    // Permission Management
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PermissionController::class, 'index'])->name('index')->middleware('permission:permissions.view');
        Route::post('/', [\App\Http\Controllers\PermissionController::class, 'store'])->name('store')->middleware('permission:permissions.assign');
        Route::post('/bulk', [\App\Http\Controllers\PermissionController::class, 'bulkStore'])->name('bulk-store')->middleware('permission:permissions.assign');
        Route::delete('/{permission}', [\App\Http\Controllers\PermissionController::class, 'destroy'])->name('destroy')->middleware('permission:permissions.assign');
        Route::post('/bulk-destroy', [\App\Http\Controllers\PermissionController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('permission:permissions.assign');
    });

    // Master Data - Tahun Akademik (Akademik only)
    Route::prefix('master/tahun-akademik')->name('tahun-akademik.')->middleware('permission:semester.create')->group(function () {
        Route::get('/', [TahunAkademikController::class, 'index'])->name('index');
        Route::post('/', [TahunAkademikController::class, 'store'])->name('store');
        Route::put('/{tahunAkademik}', [TahunAkademikController::class, 'update'])->name('update');
        Route::delete('/{tahunAkademik}', [TahunAkademikController::class, 'destroy'])->name('destroy');
        Route::post('/{tahunAkademik}/set-active', [TahunAkademikController::class, 'setActive'])->name('set-active');
        Route::post('/semester/{semester}/activate', [TahunAkademikController::class, 'activateSemester'])->name('activate-semester');
        Route::put('/semester/{semester}', [TahunAkademikController::class, 'updateSemester'])->name('update-semester');
    });

    // Master Data - Skala Nilai
    Route::prefix('master/skala-nilai')->name('skala-nilai.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SkalaNilaiController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\SkalaNilaiController::class, 'store'])->name('store');
        Route::put('/{skalaNilai}', [\App\Http\Controllers\SkalaNilaiController::class, 'update'])->name('update');
        Route::delete('/{skalaNilai}', [\App\Http\Controllers\SkalaNilaiController::class, 'destroy'])->name('destroy');
    });

    // Akademik - Setting Komponen Nilai (Per Prodi + Global)
    Route::prefix('akademik/komponen-nilai')->name('komponen-nilai.')->group(function () {
        Route::get('/', [\App\Http\Controllers\KomponenNilaiController::class, 'index'])->name('index');
        Route::get('/global/edit', [\App\Http\Controllers\KomponenNilaiController::class, 'editGlobal'])->name('editGlobal');
        Route::put('/global', [\App\Http\Controllers\KomponenNilaiController::class, 'updateGlobal'])->name('updateGlobal');
        Route::get('/{prodi}/edit', [\App\Http\Controllers\KomponenNilaiController::class, 'edit'])->name('edit');
        Route::put('/{prodi}', [\App\Http\Controllers\KomponenNilaiController::class, 'update'])->name('update');
    });

    // Dosen - Penilaian
    Route::prefix('dosen/nilai')->name('dosen.nilai.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NilaiController::class, 'index'])->name('index');
        Route::get('/{kelasMatakuliah}', [\App\Http\Controllers\NilaiController::class, 'show'])->name('show');
        Route::post('/{kelasMatakuliah}', [\App\Http\Controllers\NilaiController::class, 'store'])->name('store');
    });

    // Master Data - Program Studi
    Route::prefix('prodi')->name('prodi.')->middleware('permission:prodi.view')->group(function () {
        Route::get('/', [ProgramStudiController::class, 'index'])->name('index');
        Route::get('/{programStudi}/edit', [ProgramStudiController::class, 'edit'])->name('edit')->middleware('permission:prodi.edit');
        Route::post('/', [ProgramStudiController::class, 'store'])->name('store')->middleware('permission:prodi.create');
        Route::put('/{programStudi}', [ProgramStudiController::class, 'update'])->name('update')->middleware('permission:prodi.edit');
        Route::delete('/{programStudi}', [ProgramStudiController::class, 'destroy'])->name('destroy')->middleware('permission:prodi.delete');
    });

    // Master Data - Ruangan
    Route::prefix('ruangan')->name('ruangan.')->middleware('permission:ruangan.view')->group(function () {
        Route::get('/', [RuanganController::class, 'index'])->name('index');
        Route::post('/', [RuanganController::class, 'store'])->name('store')->middleware('permission:ruangan.create');
        Route::put('/{ruangan}', [RuanganController::class, 'update'])->name('update')->middleware('permission:ruangan.edit');
        Route::delete('/{ruangan}', [RuanganController::class, 'destroy'])->name('destroy')->middleware('permission:ruangan.delete');
    });

    // Master Data - Mata Kuliah
    Route::prefix('master/mata-kuliah')->name('mata-kuliah.')->middleware('permission:matakuliah.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\MataKuliahController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\MataKuliahController::class, 'store'])->name('store')->middleware('permission:matakuliah.create');
        Route::put('/{mataKuliah}', [\App\Http\Controllers\MataKuliahController::class, 'update'])->name('update')->middleware('permission:matakuliah.edit');
        Route::delete('/{mataKuliah}', [\App\Http\Controllers\MataKuliahController::class, 'destroy'])->name('destroy')->middleware('permission:matakuliah.delete');
        Route::post('/import', [\App\Http\Controllers\MataKuliahController::class, 'import'])->name('import')->middleware('permission:matakuliah.create');
        Route::get('/template', [\App\Http\Controllers\MataKuliahController::class, 'downloadTemplate'])->name('template');
        Route::post('/bulk-delete', [\App\Http\Controllers\MataKuliahController::class, 'bulkDelete'])->name('bulk-delete')->middleware('permission:matakuliah.delete');
        Route::post('/bulk-restore', [\App\Http\Controllers\MataKuliahController::class, 'bulkRestore'])->name('bulk-restore')->middleware('permission:matakuliah.delete');
        Route::post('/{id}/restore', [\App\Http\Controllers\MataKuliahController::class, 'restore'])->name('restore')->middleware('permission:matakuliah.delete');
        Route::delete('/{id}/force-delete', [\App\Http\Controllers\MataKuliahController::class, 'forceDelete'])->name('force-delete')->middleware('permission:matakuliah.delete');
    });

    // Menu Management
    Route::prefix('menus')->name('menus.')->middleware('permission:roles.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\MenuController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\MenuController::class, 'store'])->name('store');
        Route::put('/{menu}', [\App\Http\Controllers\MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [\App\Http\Controllers\MenuController::class, 'destroy'])->name('destroy');
        Route::post('/update-order', [\App\Http\Controllers\MenuController::class, 'updateOrder'])->name('update-order');
        Route::post('/{menu}/role-visibility', [\App\Http\Controllers\MenuController::class, 'updateRoleVisibility'])->name('role-visibility');
        Route::post('/sync-sidebar', [\App\Http\Controllers\MenuController::class, 'syncFromSidebar'])->name('sync-sidebar');
    });

    // Settings
    Route::get('/settings/ai', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.ai')->middleware('role:administrator');
    Route::post('/settings/ai', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.ai.update')->middleware('role:administrator');

    // Kurikulum OBE
    Route::prefix('kurikulum')->name('kurikulum.')->middleware('permission:kurikulum.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\KurikulumController::class, 'index'])->name('index');
        Route::get('/{kurikulum}', [\App\Http\Controllers\KurikulumController::class, 'show'])->name('show');
        Route::post('/', [\App\Http\Controllers\KurikulumController::class, 'store'])->name('store')->middleware('permission:kurikulum.create');
        Route::put('/{kurikulum}', [\App\Http\Controllers\KurikulumController::class, 'update'])->name('update')->middleware('permission:kurikulum.edit');
        Route::delete('/{kurikulum}', [\App\Http\Controllers\KurikulumController::class, 'destroy'])->name('destroy')->middleware('permission:kurikulum.delete');

        // CPL Management
        Route::post('/{kurikulum}/cpl', [\App\Http\Controllers\KurikulumController::class, 'storeCpl'])->name('cpl.store')->middleware('permission:kurikulum.edit');
        Route::put('/cpl/{cpl}', [\App\Http\Controllers\KurikulumController::class, 'updateCpl'])->name('cpl.update')->middleware('permission:kurikulum.edit');
        Route::delete('/cpl/{cpl}', [\App\Http\Controllers\KurikulumController::class, 'destroyCpl'])->name('cpl.destroy')->middleware('permission:kurikulum.delete');

        // MK Management
        Route::get('/{kurikulum}/available-mk', [\App\Http\Controllers\KurikulumController::class, 'getAvailableMk'])->name('mk.available');
        Route::post('/{kurikulum}/assign-mk', [\App\Http\Controllers\KurikulumController::class, 'assignMk'])->name('mk.assign')->middleware('permission:kurikulum.edit');
        Route::delete('/{kurikulum}/remove-mk/{mk}', [\App\Http\Controllers\KurikulumController::class, 'removeMk'])->name('mk.remove')->middleware('permission:kurikulum.edit');
        Route::post('/{kurikulum}/remove-mk-bulk', [\App\Http\Controllers\KurikulumController::class, 'removeMkBulk'])->name('mk.remove-bulk')->middleware('permission:kurikulum.delete');



        // CPMK Management
        Route::get('/{kurikulum}/mk/{mataKuliah}/cpmk', [\App\Http\Controllers\CpmkController::class, 'getByKurikulumMataKuliah'])->name('cpmk.by-mk');
        Route::post('/{kurikulum}/mk/{mataKuliah}/cpmk/generate-ai', [\App\Http\Controllers\CpmkController::class, 'generateAi'])->name('cpmk.generate-ai')->middleware('permission:kurikulum.edit');
        Route::post('/cpmk', [\App\Http\Controllers\CpmkController::class, 'store'])->name('cpmk.store')->middleware('permission:kurikulum.edit');
        Route::put('/cpmk/{cpmk}', [\App\Http\Controllers\CpmkController::class, 'update'])->name('cpmk.update')->middleware('permission:kurikulum.edit');
        Route::delete('/cpmk/{cpmk}', [\App\Http\Controllers\CpmkController::class, 'destroy'])->name('cpmk.destroy')->middleware('permission:kurikulum.delete');

        // CPL-MK Mapping
        Route::post('/{kurikulum}/toggle-cpl-mk', [\App\Http\Controllers\KurikulumController::class, 'toggleCplMk'])->name('cpl-mk.toggle')->middleware('permission:kurikulum.edit');

        // CPL-MK Mapping
        Route::post('/{kurikulum}/toggle-cpl-mk', [\App\Http\Controllers\KurikulumController::class, 'toggleCplMk'])->name('cpl-mk.toggle')->middleware('permission:kurikulum.edit');

        // Profil Lulusan
        Route::post('/{kurikulum}/pl', [\App\Http\Controllers\ProfilLulusanController::class, 'store'])->name('pl.store')->middleware('permission:kurikulum.edit');
        Route::put('/pl/{profilLulusan}', [\App\Http\Controllers\ProfilLulusanController::class, 'update'])->name('pl.update')->middleware('permission:kurikulum.edit');
        Route::delete('/pl/{profilLulusan}', [\App\Http\Controllers\ProfilLulusanController::class, 'destroy'])->name('pl.destroy')->middleware('permission:kurikulum.delete');
        Route::post('/pl/{profilLulusan}/mapping', [\App\Http\Controllers\ProfilLulusanController::class, 'updateMapping'])->name('pl.mapping')->middleware('permission:kurikulum.edit');

        // Duplicate Kurikulum
        Route::post('/{kurikulum}/duplicate', [\App\Http\Controllers\KurikulumController::class, 'duplicate'])->name('duplicate')->middleware('permission:kurikulum.create');
    });

    // RPS Settings (Must act before resource to valid conflict if ID usage)
    Route::get('/rps-settings', [\App\Http\Controllers\RpsSettingController::class, 'index'])->name('rps-settings.index');
    Route::post('/rps-settings', [\App\Http\Controllers\RpsSettingController::class, 'update'])->name('rps-settings.update');

    // RPS Module - parameter set to 'rps' to match controller $rps variable
    Route::resource('rps', \App\Http\Controllers\RpsController::class)->parameters(['rps' => 'rps']);

    // RPS Approval Workflow Routes
    Route::prefix('rps/{rps}')->name('rps.')->group(function () {
        Route::post('/submit', [\App\Http\Controllers\RpsController::class, 'submit'])->name('submit');
        Route::post('/approve-gkm', [\App\Http\Controllers\RpsController::class, 'approveByGkm'])->name('approve-gkm');
        Route::post('/approve-kaprodi', [\App\Http\Controllers\RpsController::class, 'approveByKaprodi'])->name('approve-kaprodi');
        Route::post('/bypass-approve', [\App\Http\Controllers\RpsController::class, 'bypassApprove'])->name('bypass-approve');
        Route::post('/request-revision', [\App\Http\Controllers\RpsController::class, 'requestRevision'])->name('request-revision');
        Route::put('/meta', [\App\Http\Controllers\RpsController::class, 'updateMeta'])->name('update-meta');
    });

    // AI RPS Generator
    Route::post('/ai/settings', [App\Http\Controllers\AiRpsController::class, 'storeSettings'])->name('ai.settings');
    Route::post('/ai/generate-rps', [App\Http\Controllers\AiRpsController::class, 'generate'])->name('ai.generate-rps');
    Route::post('/ai/generate-rps-full', [App\Http\Controllers\AiRpsController::class, 'generateFull'])->name('ai.generate-rps-full');
    Route::post('/ai/generate-complete', [App\Http\Controllers\AiRpsController::class, 'generateComplete'])->name('ai.generate-complete');
    Route::post('/sub-cpmk', [\App\Http\Controllers\SubCpmkController::class, 'store'])->name('sub-cpmk.store');
    Route::put('/sub-cpmk/{subCpmk}', [\App\Http\Controllers\SubCpmkController::class, 'update'])->name('sub-cpmk.update');
    Route::delete('/sub-cpmk/{subCpmk}', [\App\Http\Controllers\SubCpmkController::class, 'destroy'])->name('sub-cpmk.destroy');

    // RPS PDF Export
    Route::get('/rps/{rps}/pdf', [\App\Http\Controllers\RpsPdfController::class, 'generate'])->name('rps.pdf');
    Route::get('/rps/{rps}/pdf/preview', [\App\Http\Controllers\RpsPdfController::class, 'preview'])->name('rps.pdf.preview');

    // RPS Submit/Approve Workflow
    // RPS Submit/Approve Workflow routes are already defined in the prefix group above

    // Kelas Module
    Route::resource('kelas', \App\Http\Controllers\KelasController::class)->parameters(['kelas' => 'kelas']);
    Route::post('/kelas/bulk-destroy', [\App\Http\Controllers\KelasController::class, 'bulkDestroy'])->name('kelas.bulk-destroy');
    Route::prefix('kelas/{kelas}')->name('kelas.')->group(function () {
        // Status Update
        Route::put('/update-status', [\App\Http\Controllers\KelasController::class, 'updateStatus'])->name('update-status');
        // Mata Kuliah Assignment
        Route::post('/assign-mk', [\App\Http\Controllers\KelasController::class, 'assignMataKuliah'])->name('assign-mk');
        Route::delete('/remove-mk/{mataKuliah}', [\App\Http\Controllers\KelasController::class, 'removeMataKuliah'])->name('remove-mk');
        Route::post('/bulk-update-mk', [\App\Http\Controllers\KelasController::class, 'bulkUpdateMk'])->name('bulk-update-mk');
        Route::post('/bulk-remove-mk', [\App\Http\Controllers\KelasController::class, 'bulkRemoveMk'])->name('bulk-remove-mk');
        // Ruangan
        Route::post('/sync-ruangan', [\App\Http\Controllers\KelasController::class, 'syncRuangan'])->name('sync-ruangan');
        // Mahasiswa Enrollment
        Route::get('/enroll-candidates', [\App\Http\Controllers\KelasController::class, 'searchCandidates'])->name('enroll-candidates');
        Route::post('/enroll-mahasiswa', [\App\Http\Controllers\KelasController::class, 'bulkEnrollMahasiswa'])->name('enroll-mahasiswa');
        Route::delete('/remove-mahasiswa/{mahasiswa}', [\App\Http\Controllers\KelasController::class, 'removeMahasiswa'])->name('remove-mahasiswa');
        Route::post('/bulk-remove-mahasiswa', [\App\Http\Controllers\KelasController::class, 'bulkRemoveMahasiswa'])->name('bulk-remove-mahasiswa');
        Route::post('/generate-jadwal', [\App\Http\Controllers\KelasController::class, 'generateJadwal'])->name('generate-jadwal');
        Route::delete('/reset-jadwal', [\App\Http\Controllers\KelasController::class, 'resetJadwal'])->name('reset-jadwal');
        Route::get('/jadwals', [\App\Http\Controllers\JadwalPertemuanController::class, 'index'])->name('jadwals.index');
    });

    // Schedule Matrix View (Draft Jadwal per Kelas)
    Route::get('/kelas/{kelas}/jadwal-matrix', [\App\Http\Controllers\KelasController::class, 'jadwalMatrix'])->name('kelas.jadwal-matrix');
    Route::post('/kelas/{kelas}/update-matrix-settings', [\App\Http\Controllers\KelasController::class, 'updateMatrixSettings'])->name('kelas.update-matrix-settings');

    // Kelas MK routes
    Route::get('/kelas-mk/{kelasMatakuliah}/jadwal', [\App\Http\Controllers\JadwalPertemuanController::class, 'indexMk'])->name('kelas-mk.jadwal.index');
    Route::put('/kelas-mk/{kelasMatakuliah}/jadwal', [\App\Http\Controllers\KelasController::class, 'updateMataKuliahJadwal'])->name('kelas-mk.jadwal'); // Existing Bulk Update

    // Jadwal Pertemuan Resource (Updates & Deletes)
    Route::put('/jadwal-pertemuan/{jadwalPertemuan}', [\App\Http\Controllers\JadwalPertemuanController::class, 'update'])->name('jadwal-pertemuan.update');
    Route::delete('/jadwal-pertemuan/{jadwalPertemuan}', [\App\Http\Controllers\JadwalPertemuanController::class, 'destroy'])->name('jadwal-pertemuan.destroy');
    Route::put('/jadwal-pertemuan-bulk', [\App\Http\Controllers\JadwalPertemuanController::class, 'bulkUpdate'])->name('jadwal-pertemuan.bulk-update');
    Route::post('/jadwal-pertemuan/{id}/restore', [\App\Http\Controllers\JadwalPertemuanController::class, 'restore'])->name('jadwal-pertemuan.restore');
    Route::delete('/jadwal-pertemuan/{id}/force-delete', [\App\Http\Controllers\JadwalPertemuanController::class, 'forceDelete'])->name('jadwal-pertemuan.force-delete');
    Route::post('/jadwal-pertemuan/check-availability', [\App\Http\Controllers\JadwalPertemuanController::class, 'checkAvailability'])->name('jadwal-pertemuan.check-availability');
    Route::get('/jadwal-pertemuan/date-availability', [\App\Http\Controllers\JadwalPertemuanController::class, 'getDateAvailability'])->name('jadwal-pertemuan.date-availability');
    Route::post('/kelas-mk/{kelasMatakuliah}/assign-dosen', [\App\Http\Controllers\KelasController::class, 'assignDosen'])->name('kelas-mk.assign-dosen');
    Route::delete('/kelas-mk/{kelasMatakuliah}/remove-dosen/{dosen}', [\App\Http\Controllers\KelasController::class, 'removeDosen'])->name('kelas-mk.remove-dosen');
    // Ruangan Preference
    Route::post('/kelas-mk/{kelasMatakuliah}/add-ruangan', [\App\Http\Controllers\KelasController::class, 'addRuangan'])->name('kelas-mk.add-ruangan');
    Route::delete('/kelas-mk/{kelasMatakuliah}/remove-ruangan/{ruangan}', [\App\Http\Controllers\KelasController::class, 'removeRuangan'])->name('kelas-mk.remove-ruangan');
    Route::post('/kelas-mk/update-dosen-sesi', [\App\Http\Controllers\KelasController::class, 'updateDosenSesi'])->name('kelas-mk.update-dosen-sesi');

    // Trash Management for KelasMatakuliah
    Route::post('/kelas-mk/{id}/restore', [\App\Http\Controllers\KelasController::class, 'restoreMatakuliah'])->name('kelas-mk.restore');
    Route::delete('/kelas-mk/{id}/force-delete', [\App\Http\Controllers\KelasController::class, 'forceDeleteMatakuliah'])->name('kelas-mk.force-delete');

    // Manual Jadwal Creation (Add to existing group)
    Route::post('/kelas/{kelas}/create-manual-jadwal', [\App\Http\Controllers\KelasController::class, 'storeManualJadwal'])->name('kelas.store-manual-jadwal');

    // Manual Jadwal Creation
    Route::post('/jadwal-pertemuan', [\App\Http\Controllers\JadwalKuliahController::class, 'store'])->name('jadwal.store');
    // Manual Jadwal Update
    Route::put('/jadwal-pertemuan/{jadwalPertemuan}', [\App\Http\Controllers\JadwalKuliahController::class, 'update'])->name('jadwal.update');

    // Absensi Routes
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AbsensiController::class, 'indexPage'])->name('index');
        Route::get('/pertemuan/{jadwalPertemuan}', [\App\Http\Controllers\AbsensiController::class, 'index'])->name('pertemuan');
        Route::post('/pertemuan/{jadwalPertemuan}', [\App\Http\Controllers\AbsensiController::class, 'store'])->name('store');
        Route::post('/pertemuan/{jadwalPertemuan}/jurnal', [\App\Http\Controllers\AbsensiController::class, 'storeJurnal'])->name('jurnal.store');
        Route::post('/pertemuan/{jadwalPertemuan}/dosen-attendance', [\App\Http\Controllers\AbsensiController::class, 'storeDosenAttendance'])->name('dosen-attendance.store');
        Route::get('/rekap/kelas/{kelas}', [\App\Http\Controllers\AbsensiController::class, 'rekap'])->name('rekap-kelas');
    });

    // Pertemuan Routes (for Kelas Detail tabs)
    Route::prefix('pertemuan/{pertemuan}')->name('pertemuan.')->group(function () {
        Route::get('/', [\App\Http\Controllers\JadwalPertemuanController::class, 'show'])->name('show');
        Route::post('/absensi', [\App\Http\Controllers\JadwalPertemuanController::class, 'storeAbsensi'])->name('absensi.store');
        Route::post('/jurnal', [\App\Http\Controllers\JadwalPertemuanController::class, 'storeJurnal'])->name('jurnal.store');
    });

    // API endpoint for fetching absensis (JSON)
    Route::get('/api/pertemuan/{jadwalPertemuan}/absensis', [\App\Http\Controllers\AbsensiController::class, 'getAbsensisJson'])->name('api.pertemuan.absensis');

    // API endpoint for resetting dosen attendance
    Route::post('/api/pertemuan/{jadwalPertemuan}/reset-dosen-attendance', [\App\Http\Controllers\AbsensiController::class, 'resetDosenAttendance'])->name('api.pertemuan.reset-dosen');
    // API endpoint for updating single student status
    Route::post('/api/pertemuan/{jadwalPertemuan}/update-mahasiswa-status', [\App\Http\Controllers\AbsensiController::class, 'updateMahasiswaStatus'])->name('api.pertemuan.update-mahasiswa');
    // API endpoint for bulk updating student status
    Route::post('/api/pertemuan/{jadwalPertemuan}/bulk-update-mahasiswa-status', [\App\Http\Controllers\AbsensiController::class, 'bulkUpdateMahasiswaStatus'])->name('api.pertemuan.bulk-update-mahasiswa');

    // Dosen Module
    Route::resource('dosen', \App\Http\Controllers\DosenController::class)->except(['create', 'edit', 'show']);
    Route::prefix('dosen')->name('dosen.')->group(function () {
        Route::post('/bulk-destroy', [\App\Http\Controllers\DosenController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/bulk-restore', [\App\Http\Controllers\DosenController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('/bulk-force-delete', [\App\Http\Controllers\DosenController::class, 'bulkForceDelete'])->name('bulk-force-delete');
        Route::post('/{dosen}/restore', [\App\Http\Controllers\DosenController::class, 'restore'])->name('restore')->withTrashed();
        Route::delete('/{dosen}/force-delete', [\App\Http\Controllers\DosenController::class, 'forceDelete'])->name('force-delete')->withTrashed();
        Route::post('/{dosen}/create-account', [\App\Http\Controllers\DosenController::class, 'createAccount'])->name('create-account');
        Route::post('/bulk-create-account', [\App\Http\Controllers\DosenController::class, 'bulkCreateAccount'])->name('bulk-create-account');
        Route::post('/import', [\App\Http\Controllers\DosenController::class, 'import'])->name('import');
        Route::get('/download-template', [\App\Http\Controllers\DosenController::class, 'downloadTemplate'])->name('download-template');
    });

    // Mahasiswa Module
    Route::resource('mahasiswa', \App\Http\Controllers\MahasiswaController::class)->except(['create', 'edit', 'show']);
    Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::post('/bulk-destroy', [\App\Http\Controllers\MahasiswaController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/bulk-restore', [\App\Http\Controllers\MahasiswaController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('/bulk-force-delete', [\App\Http\Controllers\MahasiswaController::class, 'bulkForceDelete'])->name('bulk-force-delete');
        Route::post('/{mahasiswa}/restore', [\App\Http\Controllers\MahasiswaController::class, 'restore'])->name('restore')->withTrashed();
        Route::delete('/{mahasiswa}/force-delete', [\App\Http\Controllers\MahasiswaController::class, 'forceDelete'])->name('force-delete')->withTrashed();
        Route::post('/{mahasiswa}/create-account', [\App\Http\Controllers\MahasiswaController::class, 'createAccount'])->name('create-account');
        Route::post('/bulk-create-account', [\App\Http\Controllers\MahasiswaController::class, 'bulkCreateAccount'])->name('bulk-create-account');
        Route::post('/bulk-update-semester', [\App\Http\Controllers\MahasiswaController::class, 'bulkUpdateSemester'])->name('bulk-update-semester');
        Route::post('/import', [\App\Http\Controllers\MahasiswaController::class, 'import'])->name('import');
        Route::get('/download-template', [\App\Http\Controllers\MahasiswaController::class, 'downloadTemplate'])->name('download-template');
    });
    // Nilai Excel Import/Export
    Route::prefix('kelas-mk/{kelasMatakuliah}/nilai')->name('kelas.nilai.')->group(function () {
        Route::get('/template', [\App\Http\Controllers\NilaiController::class, 'downloadTemplate'])->name('template');
        Route::post('/import-preview', [\App\Http\Controllers\NilaiController::class, 'importPreview'])->name('import-preview');
        Route::post('/import', [\App\Http\Controllers\NilaiController::class, 'importStore'])->name('import-store');
    });

});
