<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'tahun_akademik_id',
        'nama',
        'tipe',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
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
