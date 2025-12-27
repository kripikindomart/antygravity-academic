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

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:users.view');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');
        Route::post('/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('permission:users.delete');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:users.edit');

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

    // Master Data - Tahun Akademik (Akademik only)
    Route::prefix('master/tahun-akademik')->name('tahun-akademik.')->middleware('permission:semester.create')->group(function () {
        Route::get('/', [TahunAkademikController::class, 'index'])->name('index');
        Route::post('/', [TahunAkademikController::class, 'store'])->name('store');
        Route::put('/{tahunAkademik}', [TahunAkademikController::class, 'update'])->name('update');
        Route::delete('/{tahunAkademik}', [TahunAkademikController::class, 'destroy'])->name('destroy');
        Route::post('/{tahunAkademik}/set-active', [TahunAkademikController::class, 'setActive'])->name('set-active');
        Route::post('/semester/{semester}/activate', [TahunAkademikController::class, 'activateSemester'])->name('activate-semester');
    });

    // Master Data - Program Studi
    Route::prefix('prodi')->name('prodi.')->middleware('permission:prodi.view')->group(function () {
        Route::get('/', [ProgramStudiController::class, 'index'])->name('index');
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

        // Duplicate Kurikulum
        Route::post('/{kurikulum}/duplicate', [\App\Http\Controllers\KurikulumController::class, 'duplicate'])->name('duplicate')->middleware('permission:kurikulum.create');
    });
});
