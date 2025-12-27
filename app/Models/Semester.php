<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Semester extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tahun_akademik_id',
        'kode',
        'tipe',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_mulai_krs',
        'tanggal_selesai_krs',
        'tanggal_uts',
        'tanggal_uas',
        'tanggal_input_nilai',
        'tanggal_deadline_nilai',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_mulai_krs' => 'date',
        'tanggal_selesai_krs' => 'date',
        'tanggal_uts' => 'date',
        'tanggal_uas' => 'date',
        'tanggal_input_nilai' => 'date',
        'tanggal_deadline_nilai' => 'date',
    ];

    /**
     * Get tahun akademik for this semester.
     */
    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class);
    }

    /**
     * Scope active semester.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get full name with tahun akademik.
     */
    public function getFullNameAttribute()
    {
        return "{$this->nama} {$this->tahunAkademik->nama}";
    }
}
