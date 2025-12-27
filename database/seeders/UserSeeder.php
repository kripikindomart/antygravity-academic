<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin - Administrator (full access)
        $admin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@uika-bogor.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081200000000',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('administrator');

        // Akademik (academic affairs)
        $akademik = User::create([
            'name' => 'Staff Akademik',
            'email' => 'akademik@uika-bogor.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $akademik->assignRole('akademik');

        // Staff Prodi
        $staffProdi = User::create([
            'name' => 'Staff Prodi Manajemen',
            'email' => 'staff.prodi@uika-bogor.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567891',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $staffProdi->assignRole('staff_prodi');

        // Keuangan
        $keuangan = User::create([
            'name' => 'Staff Keuangan',
            'email' => 'keuangan@uika-bogor.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567899',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $keuangan->assignRole('keuangan');

        // Dosen
        $dosen = User::create([
            'name' => 'Dr. Ahmad Fauzi, M.Pd',
            'email' => 'dosen@uika-bogor.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567892',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $dosen->assignRole('dosen');

        // Mahasiswa
        $mahasiswa = User::create([
            'name' => 'Budi Santoso',
            'email' => 'mahasiswa@uika-bogor.ac.id',
            'password' => Hash::make('password'),
            'phone' => '081234567893',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $mahasiswa->assignRole('mahasiswa');
    }
}
