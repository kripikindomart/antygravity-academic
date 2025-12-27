<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'jenjang',
        'akreditasi',
        'kaprodi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get dosens for this program studi.
     */
    public function dosens()
    {
        return $this->hasMany(Dosen::class);
    }

    /**
     * Get mahasiswas for this program studi.
     */
    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class);
    }

    /**
     * Scope active program studi.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
