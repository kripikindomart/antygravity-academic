<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasMkDosen extends Model
{
    protected $table = 'kelas_mk_dosen';

    protected $fillable = [
        'kelas_matakuliah_id',
        'dosen_id',
        'is_koordinator',
        'sesi_mulai',
        'sesi_selesai',
        'sks_diklaim',
    ];

    protected $casts = [
        'is_koordinator' => 'boolean',
        'sesi_mulai' => 'integer',
        'sesi_selesai' => 'integer',
        'sks_diklaim' => 'decimal:1',
    ];

    // Relationships
    public function kelasMatakuliah(): BelongsTo
    {
        return $this->belongsTo(KelasMatakuliah::class, 'kelas_matakuliah_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
