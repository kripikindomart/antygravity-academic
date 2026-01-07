<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_template_id',
        'kategori',
        'pertanyaan',
        'tipe',
        'data_source',
        'data_filter',
        'urutan',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'data_filter' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(SurveyOption::class)->orderBy('urutan');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
