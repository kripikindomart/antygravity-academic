<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProgramStudi; // Added this import for the relationship

class SkalaNilai extends Model
{
    protected $table = 'skala_nilais';

    protected $fillable = [
        'prodi_id',
        'huruf',
        'bobot',
        'min_nilai',
        'max_nilai',
        'status_lulus',
        'keterangan',
    ];

    protected $casts = [
        'bobot' => 'float',
        'min_nilai' => 'float',
        'max_nilai' => 'float',
        'status_lulus' => 'boolean',
    ];

    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function scopeForProdi($query, $prodiId)
    {
        return $query->where(function ($q) use ($prodiId) {
            $q->where('prodi_id', $prodiId)
                ->orWhereNull('prodi_id');
        })->orderBy('prodi_id', 'desc'); // Prioritize specific prodi
    }
}
