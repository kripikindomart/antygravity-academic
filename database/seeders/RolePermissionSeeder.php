<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Users Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles & Permissions
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.assign',

            // Program Studi
            'prodi.view',
            'prodi.create',
            'prodi.edit',
            'prodi.delete',

            // Kurikulum & CPL/CPMK (OBE)
            'kurikulum.view',
            'kurikulum.create',
            'kurikulum.edit',
            'kurikulum.delete',
            'cpl.view',
            'cpl.create',
            'cpl.edit',
            'cpl.delete',
            'cpmk.view',
            'cpmk.create',
            'cpmk.edit',
            'cpmk.delete',

            // Mata Kuliah
            'matakuliah.view',
            'matakuliah.create',
            'matakuliah.edit',
            'matakuliah.delete',

            // Ruangan
            'ruangan.view',
            'ruangan.create',
            'ruangan.edit',
            'ruangan.delete',

            // Semester / Tahun Akademik
            'semester.view',
            'semester.create',
            'semester.edit',
            'semester.delete',

            // Jadwal
            'jadwal.view',
            'jadwal.create',
            'jadwal.edit',
            'jadwal.delete',
            'jadwal.approve',

            // SK Mengajar
            'sk_mengajar.view',
            'sk_mengajar.create',
            'sk_mengajar.edit',
            'sk_mengajar.delete',
            'sk_mengajar.generate',

            // Absensi
            'absensi.view',
            'absensi.create',
            'absensi.edit',
            'absensi.rekap',

            // Jurnal Perkuliahan
            'jurnal.view',
            'jurnal.create',
            'jurnal.edit',

            // Nilai
            'nilai.view',
            'nilai.create',
            'nilai.edit',
            'nilai.approve',

            // Survei Evaluasi
            'survei.view',
            'survei.create',
            'survei.respond',
            'survei.hasil',

            // Laporan
            'laporan.view',
            'laporan.export',

            // Settings
            'settings.view',
            'settings.edit',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Administrator - Super Admin (all permissions)
        $administrator = Role::create(['name' => 'administrator']);
        $administrator->givePermissionTo(Permission::all());

        // Akademik - Academic affairs (limited to academic management)
        $akademik = Role::create(['name' => 'akademik']);
        $akademik->givePermissionTo([
            'dashboard.view',
            'users.view',
            'prodi.view',
            'prodi.create',
            'prodi.edit',
            'kurikulum.view',
            'kurikulum.create',
            'kurikulum.edit',
            'kurikulum.delete',
            'cpl.view',
            'cpl.create',
            'cpl.edit',
            'cpl.delete',
            'cpmk.view',
            'cpmk.create',
            'cpmk.edit',
            'cpmk.delete',
            'matakuliah.view',
            'matakuliah.create',
            'matakuliah.edit',
            'matakuliah.delete',
            'ruangan.view',
            'ruangan.create',
            'ruangan.edit',
            'semester.view',
            'semester.create',
            'semester.edit',
            'jadwal.view',
            'jadwal.create',
            'jadwal.edit',
            'jadwal.delete',
            'jadwal.approve',
            'sk_mengajar.view',
            'sk_mengajar.create',
            'sk_mengajar.edit',
            'sk_mengajar.generate',
            'absensi.view',
            'absensi.rekap',
            'jurnal.view',
            'nilai.view',
            'nilai.approve',
            'survei.view',
            'survei.create',
            'survei.hasil',
            'laporan.view',
            'laporan.export',
        ]);

        // Staff Prodi
        $staffProdi = Role::create(['name' => 'staff_prodi']);
        $staffProdi->givePermissionTo([
            'dashboard.view',
            'users.view',
            'prodi.view',
            'kurikulum.view',
            'kurikulum.create',
            'kurikulum.edit',
            'cpl.view',
            'cpl.create',
            'cpl.edit',
            'cpmk.view',
            'cpmk.create',
            'cpmk.edit',
            'matakuliah.view',
            'matakuliah.create',
            'matakuliah.edit',
            'ruangan.view',
            'semester.view',
            'jadwal.view',
            'jadwal.create',
            'jadwal.edit',
            'sk_mengajar.view',
            'sk_mengajar.create',
            'sk_mengajar.generate',
            'absensi.view',
            'absensi.rekap',
            'jurnal.view',
            'nilai.view',
            'survei.view',
            'survei.create',
            'survei.hasil',
            'laporan.view',
            'laporan.export',
        ]);

        // Dosen
        $dosen = Role::create(['name' => 'dosen']);
        $dosen->givePermissionTo([
            'dashboard.view',
            'kurikulum.view',
            'cpl.view',
            'cpmk.view',
            'matakuliah.view',
            'jadwal.view',
            'absensi.view',
            'absensi.create',
            'absensi.edit',
            'jurnal.view',
            'jurnal.create',
            'jurnal.edit',
            'nilai.view',
            'nilai.create',
            'nilai.edit',
            'survei.hasil',
        ]);

        // Mahasiswa
        $mahasiswa = Role::create(['name' => 'mahasiswa']);
        $mahasiswa->givePermissionTo([
            'dashboard.view',
            'kurikulum.view',
            'cpl.view',
            'matakuliah.view',
            'jadwal.view',
            'absensi.view',
            'nilai.view',
            'survei.respond',
        ]);

        // Keuangan - Finance staff for honorarium reporting
        $keuangan = Role::create(['name' => 'keuangan']);
        $keuangan->givePermissionTo([
            'dashboard.view',
            'prodi.view',
            'jadwal.view',
            'absensi.view',
            'absensi.rekap',
            'laporan.view',
            'laporan.export',
        ]);
    }
}
