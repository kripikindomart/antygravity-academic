<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kurikulum extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'prodi_id',
        'kode',
        'nama',
        'tahun',
        'total_sks_wajib',
        'total_sks_pilihan',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'total_sks_wajib' => 'integer',
        'total_sks_pilihan' => 'integer',
        'is_active' => 'boolean',
    ];

    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function mataKuliahs()
    {
        return $this->belongsToMany(MataKuliah::class, 'kurikulum_mata_kuliah')
            ->withPivot('semester_rekomendasi')
            ->withTimestamps();
    }
}
