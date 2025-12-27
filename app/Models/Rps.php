<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rps extends Model
{
    protected $table = 'rps';

    protected $fillable = [
        'mata_kuliah_id',
        'semester_id',
        'kurikulum_id',
        'dosen_id', // Pengembang RPS
        'nomor',
        'tanggal_penyusunan',
        'status', // draft, diajukan, disetujui
        'deskripsi',
        'bahan_kajian',
        'pustaka_utama',
        'pustaka_pendukung',
    ];

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(RpsDetail::class)->orderBy('pertemuan');
    }
}
