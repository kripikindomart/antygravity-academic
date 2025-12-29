<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasMatakuliah extends Model
{
    protected $table = 'kelas_matakuliah';

    protected $fillable = [
        'kelas_id',
        'mata_kuliah_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'sesi_per_pertemuan',
        'use_custom_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'total_sesi',
        'pertemuan_uts',
        'pertemuan_uas',
    ];

    protected $casts = [
        'use_custom_periode' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'sesi_per_pertemuan' => 'integer',
        'total_sesi' => 'integer',
        'pertemuan_uts' => 'integer',
        'pertemuan_uas' => 'integer',
    ];

    // Relationships
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function dosens(): HasMany
    {
        return $this->hasMany(KelasMkDosen::class, 'kelas_matakuliah_id');
    }

    // Accessors
    public function getJadwalDisplayAttribute(): string
    {
        if (!$this->hari)
            return '-';

        $hari = ucfirst($this->hari);
        $jam = $this->jam_mulai ? substr($this->jam_mulai, 0, 5) . '-' . substr($this->jam_selesai, 0, 5) : '';

        return "{$hari} {$jam}";
    }
}
