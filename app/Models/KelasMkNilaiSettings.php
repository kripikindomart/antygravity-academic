<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasMkNilaiSettings extends Model
{
    protected $table = 'kelas_mk_nilai_settings';

    protected $fillable = [
        'kelas_matakuliah_id',
        'dosen_id',
        'deadline',
        'allow_view_others',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'allow_view_others' => 'boolean',
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
