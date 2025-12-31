<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rps extends Model
{
    protected $table = 'rps';

    protected $fillable = [
        'mata_kuliah_id',
        'semester_id',
        'kurikulum_id',
        'dosen_id', // Pengembang RPS
        'nomor',
        'tanggal_penyusunan',
        'status', // draft, diajukan, disetujui
        'deskripsi',
        'bahan_kajian',
        'pustaka_utama',
        'pustaka_pendukung',
        // Approval fields
        'approval_status',
        'verification_code',
        'approved_by_gkm_id',
        'approved_by_gkm_at',
        'gkm_notes',
        'approved_by_kaprodi_id',
        'approved_by_kaprodi_at',
        'kaprodi_notes',
    ];

    protected $appends = ['status'];

    protected $casts = [
        'tanggal_penyusunan' => 'date',
        'approved_by_gkm_at' => 'datetime',
        'approved_by_kaprodi_at' => 'datetime',
    ];

    // Accessor to make 'status' alias to 'approval_status'
    public function getStatusAttribute()
    {
        return $this->attributes['approval_status'] ?? self::STATUS_DRAFT;
    }

    // Mutator to redirect 'status' writes to 'approval_status'
    public function setStatusAttribute($value)
    {
        $this->attributes['approval_status'] = $value;
        // Optionally keep legacy column in sync if needed, but better to rely on approval_status
        $this->attributes['status'] = $value;
    }

    // Approval status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GKM_APPROVED = 'gkm_approved';
    const STATUS_APPROVED = 'approved';
    const STATUS_REVISION = 'revision';

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function pengembang()
    {
        return $this->belongsToMany(Dosen::class, 'rps_pengembang', 'rps_id', 'dosen_id')->withTimestamps();
    }

    public function details(): HasMany
    {
        return $this->hasMany(RpsDetail::class)->orderBy('pertemuan');
    }

    // Approval relationships
    public function approvedByGkm(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'approved_by_gkm_id');
    }

    public function approvedByKaprodi(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'approved_by_kaprodi_id');
    }

    // Helper methods
    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->approval_status, [self::STATUS_DRAFT, self::STATUS_REVISION]);
    }

    public function canBeApprovedByGkm(): bool
    {
        return $this->approval_status === self::STATUS_SUBMITTED;
    }

    public function canBeApprovedByKaprodi(): bool
    {
        return $this->approval_status === self::STATUS_GKM_APPROVED;
    }

    /**
     * Generate verification code for this RPS
     */
    public function generateVerificationCode(): string
    {
        $prodi = $this->mataKuliah?->prodi;
        $prodiKode = $prodi?->kode ?? 'XXX';
        $year = date('Y');
        $id = str_pad($this->id, 5, '0', STR_PAD_LEFT);

        return "RPS-{$prodiKode}-{$year}-{$id}";
    }
}
