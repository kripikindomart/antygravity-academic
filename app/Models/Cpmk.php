<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpmk extends Model
{
    protected $table = 'cpmks';

    protected $fillable = [
        'cpl_id',
        'mata_kuliah_id',
        'kode',
        'deskripsi',
        'bobot',
        'urutan',
    ];

    protected $casts = [
        'bobot' => 'decimal:2',
        'urutan' => 'integer',
    ];

    /**
     * CPL
     */
    public function cpl(): BelongsTo
    {
        return $this->belongsTo(Cpl::class);
    }

    /**
     * Mata Kuliah
     */
    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    /**
     * Sub-CPMK
     */
    public function subCpmks(): HasMany
    {
        return $this->hasMany(SubCpmk::class)->orderBy('urutan');
    }

    /**
     * Get full code with CPL reference
     */
    public function getFullCodeAttribute(): string
    {
        return $this->cpl?->kode . ' - ' . $this->kode;
    }
}
