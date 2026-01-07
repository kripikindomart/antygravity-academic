<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SurveyPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_template_id',
        'tahun_akademik_id',
        'nama',
        'slug',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'is_mandatory',
        'allow_guest',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_mandatory' => 'boolean',
        'allow_guest' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($period) {
            if (empty($period->slug)) {
                $period->slug = self::generateUniqueSlug();
            }
        });
    }

    public static function generateUniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(8));
        } while (self::where('slug', $slug)->exists());

        return $slug;
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'tahun_akademik_id');
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SurveyTarget::class);
    }

    public function isActive(): bool
    {
        $now = now()->toDateString();
        return $this->status === 'active'
            && $this->tanggal_mulai <= $now
            && $this->tanggal_selesai >= $now;
    }
}
