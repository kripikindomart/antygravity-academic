<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KomponenNilai extends Model
{
    protected $table = 'komponen_nilais';

    protected $fillable = [
        'prodi_id',
        'nama',
        'bobot',
        'is_active',
        'source_type', // manual, kehadiran
    ];

    protected $casts = [
        'bobot' => 'float',
        'is_active' => 'boolean',
    ];

    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function nilaiMahasiswas()
    {
        return $this->hasMany(NilaiMahasiswa::class, 'komponen_nilai_id');
    }
}
