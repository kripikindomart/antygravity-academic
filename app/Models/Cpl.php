<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpl extends Model
{
    use SoftDeletes;

    protected $table = 'cpls';

    protected $fillable = [
        'kurikulum_id',
        'kode',
        'deskripsi',
        'kategori',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    /**
     * Kurikulum
     */
    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class);
    }

    /**
     * CPMK - Capaian Pembelajaran Mata Kuliah
     */
    public function cpmks(): HasMany
    {
        return $this->hasMany(Cpmk::class)->orderBy('urutan');
    }

    /**
     * Get category label
     */
    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'sikap' => 'Sikap',
            'pengetahuan' => 'Pengetahuan',
            'keterampilan_umum' => 'Keterampilan Umum',
            'keterampilan_khusus' => 'Keterampilan Khusus',
            default => $this->kategori ?? '-',
        };
    }
}
