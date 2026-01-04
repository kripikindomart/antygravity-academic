<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JadwalPertemuan extends Model
{
    use SoftDeletes;

    protected $table = 'jadwal_pertemuans';

    protected $fillable = [
        'jadwal_id',
        'pertemuan_ke', // Urutan pertemuan fisik (1, 2, 3...)
        'tanggal',
        'jam_mulai', // Override time
        'jam_selesai', // Override time
        'dosen_id',
        'dosen_jam_masuk', // Dosen attendance
        'dosen_jam_keluar', // Dosen attendance
        'dosen_hadir', // Dosen attendance flag
        'ruangan_id',
        'tipe', // Kuliah, UTS, UAS
        'status', // Terjadwal, Selesai, Dibatalkan
        'mode', // online, offline, hybrid
        'catatan',
        'sesi_mulai',
        'sesi_selesai',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'pertemuan_ke' => 'integer',
        'sesi_mulai' => 'integer',
        'sesi_selesai' => 'integer',
        'dosen_hadir' => 'boolean',
    ];

    // Relationships
    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function jurnal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Jurnal::class);
    }

    public function absensis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Absensi::class);
    }
}
