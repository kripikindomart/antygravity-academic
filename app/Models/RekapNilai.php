<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\KelasMatakuliah;
use App\Models\Mahasiswa;
use App\Models\User;

class RekapNilai extends Model
{
    protected $table = 'rekap_nilais';

    protected $fillable = [
        'kelas_matakuliah_id',
        'mahasiswa_id',
        'nilai_angka',
        'nilai_huruf',
        'nilai_indeks',
        'status',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'nilai_angka' => 'float',
        'nilai_indeks' => 'float',
        'published_at' => 'datetime',
    ];

    public function kelasMatakuliah()
    {
        return $this->belongsTo(KelasMatakuliah::class, 'kelas_matakuliah_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
