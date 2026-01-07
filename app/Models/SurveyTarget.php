<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_period_id',
        'kelas_matakuliah_id',
        'dosen_id',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(SurveyPeriod::class, 'survey_period_id');
    }

    public function kelasMatakuliah(): BelongsTo
    {
        return $this->belongsTo(KelasMatakuliah::class, 'kelas_matakuliah_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
