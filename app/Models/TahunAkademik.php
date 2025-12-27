<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAkademik extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'tahun_mulai',
        'tahun_selesai',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get semesters for this tahun akademik.
     */
    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    /**
     * Scope active tahun akademik.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the active semester for this tahun akademik.
     */
    public function activeSemester()
    {
        return $this->semesters()->where('is_active', true)->first();
    }
}
