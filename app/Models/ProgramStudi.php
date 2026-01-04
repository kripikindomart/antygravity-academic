<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'jenjang',
        'visi',
        'misi',
        'tujuan',
        'akreditasi',
        'no_sk_akreditasi',
        'tanggal_akreditasi',
        'masa_berlaku_akreditasi',
        'sertifikat_akreditasi',
        'email',
        'telepon',
        'alamat',
        'website',
        'logo',
        'kaprodi_id',
        'is_kaprodi_plt',
        'sekretaris_id',
        'is_sekretaris_plt',
        'gkm_id',
        'staf_prodi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_kaprodi_plt' => 'boolean',
        'is_sekretaris_plt' => 'boolean',
        'tanggal_akreditasi' => 'date',
        'masa_berlaku_akreditasi' => 'date',
        'staf_prodi' => 'array',
    ];

    /**
     * Get dosens for this program studi.
     */
    public function dosens()
    {
        return $this->hasMany(Dosen::class, 'prodi_id');
    }

    /**
     * Get kelas for this program studi.
     */
    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'prodi_id');
    }

    /**
     * Get mahasiswas for this program studi.
     */
    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class, 'prodi_id');
    }

    /**
     * Get komponen nilais for this program studi.
     */
    public function komponenNilais()
    {
        return $this->hasMany(KomponenNilai::class, 'prodi_id');
    }

    /**
     * Get jabatan struktural for this program studi.
     */
    public function jabatanStruktural()
    {
        return $this->hasMany(JabatanStruktural::class);
    }

    /**
     * Get Kaprodi (direct FK relation).
     */
    public function kaprodi()
    {
        return $this->belongsTo(Dosen::class, 'kaprodi_id');
    }

    /**
     * Get Sekretaris Prodi (direct FK relation).
     */
    public function sekretaris()
    {
        return $this->belongsTo(Dosen::class, 'sekretaris_id');
    }

    /**
     * Get Gugus Kendali Mutu (direct FK relation).
     */
    public function gkm()
    {
        return $this->belongsTo(Dosen::class, 'gkm_id');
    }

    /**
     * Scope active program studi.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
