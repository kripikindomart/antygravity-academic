<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar' => $request->user()->avatar,
                    'roles' => $request->user()->roles->pluck('name'),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info'),
            ],
            'app' => [
                'name' => config('app.name'),
                'locale' => app()->getLocale(),
            ],
            'sidebarMenus' => fn() => $this->getSidebarMenus($request),
        ];
    }

    /**
     * Get sidebar menus for the current user
     */
    protected function getSidebarMenus(Request $request): array
    {
        if (!$request->user()) {
            return [];
        }

        $userPermissions = $request->user()->getAllPermissions()->pluck('name')->toArray();
        $userRoleIds = $request->user()->roles->pluck('id')->toArray();

        $menus = \App\Models\Menu::active()
            ->root()
            ->with([
                'children' => function ($q) {
                    $q->active()->orderBy('order');
                },
                'roles'
            ])
            ->orderBy('section')
            ->orderBy('order')
            ->get()
            ->filter(function ($menu) use ($userPermissions, $userRoleIds) {
                // Check role override first
                $roleOverride = $menu->roles->whereIn('id', $userRoleIds)->first();

                if ($roleOverride) {
                    return $roleOverride->pivot->is_visible;
                }

                // Fall back to permission check
                if (empty($menu->permission)) {
                    return true; // No permission required (public)
                }

                return in_array($menu->permission, $userPermissions);
            })
            ->values()
            ->toArray();

        // Group by section with custom ordering
        $sectionOrder = [
            'Menu Utama' => 1,
            'Akademik' => 2,
            'Master Data' => 3,
            'Manajemen' => 4,
            'Laporan' => 5,
        ];

        $grouped = [];
        foreach ($menus as $menu) {
            $section = $menu['section'] ?? 'Lainnya';
            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }
            $grouped[$section][] = $menu;
        }

        // Sort sections by predefined order
        uksort($grouped, function ($a, $b) use ($sectionOrder) {
            $orderA = $sectionOrder[$a] ?? 99;
            $orderB = $sectionOrder[$b] ?? 99;
            return $orderA <=> $orderB;
        });

        return $grouped;
    }
}
