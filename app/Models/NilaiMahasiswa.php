<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiMahasiswa extends Model
{
    protected $table = 'nilai_mahasiswas';

    protected $fillable = [
        'kelas_matakuliah_id',
        'komponen_nilai_id',
        'mahasiswa_id',
        'nilai',
        'grader_id',
        'dosen_id',
        'feedback',
        'status',
    ];

    protected $casts = [
        'nilai' => 'float',
    ];

    public function komponenNilai()
    {
        return $this->belongsTo(KomponenNilai::class, 'komponen_nilai_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'grader_id');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function kelasMatakuliah()
    {
        return $this->belongsTo(KelasMatakuliah::class, 'kelas_matakuliah_id');
    }
}
