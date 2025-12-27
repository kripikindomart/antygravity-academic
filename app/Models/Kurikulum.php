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

    /**
     * CPL - Capaian Pembelajaran Lulusan
     */
    public function cpls()
    {
        return $this->hasMany(Cpl::class)->orderBy('urutan');
    }

    public function profilLulusans()
    {
        return $this->hasMany(ProfilLulusan::class);
    }

    public function mataKuliahs()
    {
        return $this->belongsToMany(MataKuliah::class, 'kurikulum_mata_kuliah')
            ->withPivot('semester')
            ->withTimestamps();
    }

    public function getTotalSksAttribute(): int
    {
        return $this->total_sks_wajib + $this->total_sks_pilihan;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
