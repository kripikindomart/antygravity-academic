<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'href',
        'permission',
        'parent_id',
        'section',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Parent menu
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Child menus
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    /**
     * Roles with explicit access override
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'menu_role')
            ->withPivot('is_visible')
            ->withTimestamps();
    }

    /**
     * Scope for active menus
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root menus (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get menus accessible by a user
     */
    public static function getForUser($user)
    {
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        $userRoleIds = $user->roles->pluck('id')->toArray();

        return self::active()
            ->root()
            ->with([
                'children' => function ($q) {
                    $q->active()->orderBy('order');
                }
            ])
            ->orderBy('section')
            ->orderBy('order')
            ->get()
            ->filter(function ($menu) use ($userPermissions, $userRoleIds) {
                // Check role override first
                $roleOverride = $menu->roles()
                    ->whereIn('role_id', $userRoleIds)
                    ->first();

                if ($roleOverride) {
                    return $roleOverride->pivot->is_visible;
                }

                // Fall back to permission check
                if (empty($menu->permission)) {
                    return true; // No permission required
                }

                return in_array($menu->permission, $userPermissions);
            });
    }
}
