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

    // Master Data - Tahun Akademik
    Route::prefix('master/tahun-akademik')->name('tahun-akademik.')->middleware('permission:semester.view')->group(function () {
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
    Route::prefix('mata-kuliah')->name('mata-kuliah.')->middleware('permission:matakuliah.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\MataKuliahController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\MataKuliahController::class, 'store'])->name('store')->middleware('permission:matakuliah.create');
        Route::put('/{mataKuliah}', [\App\Http\Controllers\MataKuliahController::class, 'update'])->name('update')->middleware('permission:matakuliah.edit');
        Route::delete('/{mataKuliah}', [\App\Http\Controllers\MataKuliahController::class, 'destroy'])->name('destroy')->middleware('permission:matakuliah.delete');
    });
});
