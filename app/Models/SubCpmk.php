<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCpmk extends Model
{
    protected $table = 'sub_cpmks';

    protected $fillable = [
        'cpmk_id',
        'kode',
        'deskripsi',
        'pertemuan_range',
        'urutan',
    ];

    protected $casts = [
        'urutan' => 'integer',
    ];

    /**
     * CPMK
     */
    public function cpmk(): BelongsTo
    {
        return $this->belongsTo(Cpmk::class);
    }

    /**
     * Get full code with CPMK reference
     */
    public function getFullCodeAttribute(): string
    {
        return $this->cpmk?->kode . '.' . $this->kode;
    }

    /**
     * Parse pertemuan range to array
     */
    public function getPertemuanArrayAttribute(): array
    {
        if (!$this->pertemuan_range) {
            return [];
        }

        $parts = explode(',', $this->pertemuan_range);
        $result = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part);
                for ($i = (int) $start; $i <= (int) $end; $i++) {
                    $result[] = $i;
                }
            } else {
                $result[] = (int) $part;
            }
        }

        return array_unique($result);
    }
}
