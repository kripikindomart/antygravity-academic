<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RpsDetail extends Model
{
    protected $table = 'rps_details';

    protected $fillable = [
        'rps_id',
        'pertemuan', // 1-16
        'sub_cpmk_id',
        'materi',
        'metode',
        'indikator',
        'bobot_nilai',
    ];

    protected $casts = [
        'pertemuan' => 'integer',
        'bobot_nilai' => 'decimal:2',
    ];

    public function rps(): BelongsTo
    {
        return $this->belongsTo(Rps::class);
    }

    public function subCpmk(): BelongsTo
    {
        return $this->belongsTo(SubCpmk::class);
    }
}
