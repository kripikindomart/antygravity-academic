<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'kapasitas',
        'gedung',
        'lantai',
        'is_active',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'lantai' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope active ruangan.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
