<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jadwal extends Model
{
    use SoftDeletes;

    protected $table = 'jadwals';

    protected $fillable = [
        'kelas_id',
        'semester_id',
        'mata_kuliah_id',
        'ruangan_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
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

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function pertemuans(): HasMany
    {
        return $this->hasMany(JadwalPertemuan::class);
    }
}
