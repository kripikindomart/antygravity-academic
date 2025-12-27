<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JabatanStruktural extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_studi_id',
        'dosen_id',
        'jabatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'sk_nomor',
        'ttd_path',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get program studi.
     */
    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    /**
     * Get dosen.
     */
    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    /**
     * Scope active jabatan.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
