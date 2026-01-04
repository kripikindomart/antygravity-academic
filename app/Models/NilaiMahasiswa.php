<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiMahasiswa extends Model
{
    protected $table = 'nilai_mahasiswas';

    protected $fillable = [
        'komponen_nilai_id',
        'mahasiswa_id',
        'nilai',
        'grader_id',
        'feedback',
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
}
