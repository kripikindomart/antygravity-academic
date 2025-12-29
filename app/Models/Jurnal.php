<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurnal extends Model
{
    use SoftDeletes;

    protected $table = 'jurnals';

    protected $fillable = [
        'jadwal_pertemuan_id',
        'materi',
        'aktivitas',
        'capaian',
        'sub_cpmk_id',
        'jumlah_hadir',
        'jumlah_izin',
        'jumlah_sakit',
        'jumlah_alpha',
        'catatan',
        'dosen_id',
        'bukti_perkuliahan',
    ];

    public function jadwalPertemuan(): BelongsTo
    {
        return $this->belongsTo(JadwalPertemuan::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }
}
