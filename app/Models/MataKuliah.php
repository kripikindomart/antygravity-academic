<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MataKuliah extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'prodi_id',
        'kode',
        'nama',
        'nama_en',
        'sks_teori',
        'sks_praktik',
        'semester',
        'jenis',
        'deskripsi',
        'deskripsi_en',
        'prasyarat_id',
        'is_active',
    ];

    protected $casts = [
        'sks_teori' => 'integer',
        'sks_praktik' => 'integer',
        'semester' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the program studi that owns the mata kuliah.
     */
    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    /**
     * Get the prasyarat mata kuliah.
     */
    public function prasyarat()
    {
        return $this->belongsTo(MataKuliah::class, 'prasyarat_id');
    }

    /**
     * Get the mata kuliahs that require this mata kuliah.
     */
    public function requiredBy()
    {
        return $this->hasMany(MataKuliah::class, 'prasyarat_id');
    }

    /**
     * Get the kurikulums that belong to the mata kuliah.
     */
    public function kurikulums()
    {
        return $this->belongsToMany(Kurikulum::class, 'kurikulum_mata_kuliah')
            ->withPivot('semester_rekomendasi')
            ->withTimestamps();
    }

    /**
     * Get total SKS attribute.
     */
    public function getTotalSksAttribute()
    {
        return $this->sks_teori + $this->sks_praktik;
    }
}
