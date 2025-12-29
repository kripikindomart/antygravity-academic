<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    use SoftDeletes;

    protected $table = 'dosens';

    protected $fillable = [
        'user_id',
        'prodi_id',
        'nip',
        'nidn',
        'nama',
        'gelar_depan',
        'gelar_belakang',
        'email',
        'telepon',
        'jenis_kelamin',
        'tanggal_lahir',
        'tempat_lahir',
        'alamat',
        'jabatan_fungsional',
        'jabatan_struktural',
        'pangkat_golongan',
        'bidang_keahlian',
        'pendidikan_terakhir',
        'foto',
        'is_dosen_luar',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'is_dosen_luar' => 'boolean',
    ];

    protected $appends = ['nama_gelar'];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function kelasMatakuliahs(): HasMany
    {
        return $this->hasMany(KelasMkDosen::class);
    }

    // Accessors
    public function getNamaGelarAttribute(): string
    {
        $nama = $this->nama ?? '';
        if ($this->gelar_depan) {
            $nama = $this->gelar_depan . ' ' . $nama;
        }
        if ($this->gelar_belakang) {
            $nama .= ', ' . $this->gelar_belakang;
        }
        return trim($nama);
    }

    public function getNamaLengkapAttribute(): string
    {
        $nama = $this->nama;
        if ($this->gelar_depan) {
            $nama = $this->gelar_depan . ' ' . $nama;
        }
        if ($this->gelar_belakang) {
            $nama .= ', ' . $this->gelar_belakang;
        }
        return $nama;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeByProdi($query, $prodiId)
    {
        return $query->where('prodi_id', $prodiId);
    }
}
