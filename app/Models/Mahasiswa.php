<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Mahasiswa extends Model
{
    use SoftDeletes;

    protected $table = 'mahasiswas';

    protected $fillable = [
        'user_id',
        'prodi_id',
        'nim',
        'nama',
        'email',
        'no_hp',
        'alamat',
        'tanggal_lahir',
        'jenis_kelamin',
        'angkatan',
        'tahun_akademik_masuk_id',
        'semester_masuk_id',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function tahunAkademikMasuk(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_masuk_id');
    }

    public function semesterMasuk(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_masuk_id');
    }

    public function kelasList(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_mahasiswa')
            ->withPivot('status')
            ->withTimestamps();
    }
}
