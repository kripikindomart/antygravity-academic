<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'address',
        'gender',
        'birth_date',
        'birth_place',
        'is_active',
        'openai_api_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'openai_api_key', // Hide from array/JSON by default
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'openai_api_key' => 'encrypted',
        ];
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Scope active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if user is Akademik (super admin).
     */
    public function isAkademik(): bool
    {
        return $this->hasRole('akademik');
    }

    /**
     * Check if user is Staff Prodi.
     */
    public function isStaffProdi(): bool
    {
        return $this->hasRole('staff_prodi');
    }

    /**
     * Check if user is Dosen.
     */
    public function isDosen(): bool
    {
        return $this->hasRole('dosen');
    }

    /**
     * Check if user is Mahasiswa.
     */
    public function isMahasiswa(): bool
    {
        return $this->hasRole('mahasiswa');
    }

    /**
     * Get the program studis that the user belongs to.
     */
    public function prodis()
    {
        return $this->belongsToMany(ProgramStudi::class, 'user_prodi', 'user_id', 'program_studi_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
