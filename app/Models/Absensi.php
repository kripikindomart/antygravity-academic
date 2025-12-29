<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Absensi extends Model
{
    use SoftDeletes;

    protected $table = 'absensis';

    protected $fillable = [
        'jadwal_pertemuan_id',
        'mahasiswa_id',
        'status', // hadir, izin, sakit, alpha
        'keterangan',
        'jam_masuk',
        'jam_keluar',
        'input_by',
    ];

    public function jadwalPertemuan(): BelongsTo
    {
        return $this->belongsTo(JadwalPertemuan::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
