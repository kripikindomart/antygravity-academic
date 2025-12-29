<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    use SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = [
        'semester_id',
        'prodi_id',
        'nama',
        'kode',
        'persen_online',
        'persen_offline',
        'platform_online',
        'link_online',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
    ];

    protected $casts = [
        'persen_online' => 'integer',
        'persen_offline' => 'integer',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    // Relationships
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function ruangans(): BelongsToMany
    {
        return $this->belongsToMany(Ruangan::class, 'kelas_ruangan')
            ->withPivot('prioritas')
            ->withTimestamps()
            ->orderByPivot('prioritas');
    }

    public function mahasiswas(): BelongsToMany
    {
        return $this->belongsToMany(Mahasiswa::class, 'kelas_mahasiswa')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function mataKuliahs(): BelongsToMany
    {
        return $this->belongsToMany(MataKuliah::class, 'kelas_matakuliah')
            ->withPivot([
                'hari',
                'jam_mulai',
                'jam_selesai',
                'sesi_per_pertemuan',
                'use_custom_periode',
                'tanggal_mulai',
                'tanggal_selesai',
                'total_sesi',
                'pertemuan_uts',
                'pertemuan_uas'
            ])
            ->withTimestamps();
    }

    public function kelasMatakuliahs(): HasMany
    {
        return $this->hasMany(KelasMatakuliah::class);
    }

    // Accessors
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'bg-yellow-100 text-yellow-800',
            'ready' => 'bg-blue-100 text-blue-800',
            'generated' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'draft');
    }

    public function scopeBySemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeByProdi($query, $prodiId)
    {
        return $query->where('prodi_id', $prodiId);
    }
}
