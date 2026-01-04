<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions grouped by module.
     */
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();

        // Group by module (first part of permission name)
        $grouped = $permissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        })->map(function ($perms, $module) {
            return [
                'module' => $module,
                'permissions' => $perms->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'action' => explode('.', $p->name)[1] ?? $p->name,
                        'roles_count' => $p->roles()->count(),
                    ];
                })->values(),
            ];
        })->values();

        // Get all modules for quick add
        $modules = $permissions->map(function ($p) {
            return explode('.', $p->name)[0];
        })->unique()->values();

        // Standard actions
        $standardActions = ['view', 'create', 'edit', 'delete'];

        return Inertia::render('Permissions/Index', [
            'groupedPermissions' => $grouped,
            'modules' => $modules,
            'standardActions' => $standardActions,
            'totalPermissions' => $permissions->count(),
        ]);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:permissions,name', 'regex:/^[a-z_]+\.[a-z_]+$/'],
        ], [
            'name.regex' => 'Format permission harus: module.action (contoh: menus.view)',
            'name.unique' => 'Permission ini sudah ada.',
        ]);

        Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        return back()->with('success', "Permission '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Bulk create permissions for a module.
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'module' => ['required', 'string', 'regex:/^[a-z_]+$/'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*' => ['string'],
        ]);

        $created = [];
        foreach ($validated['actions'] as $action) {
            $name = $validated['module'] . '.' . $action;
            if (!Permission::where('name', $name)->exists()) {
                Permission::create(['name' => $name, 'guard_name' => 'web']);
                $created[] = $name;
            }
        }

        if (count($created) === 0) {
            return back()->with('info', 'Semua permission sudah ada.');
        }

        return back()->with('success', count($created) . ' permission berhasil ditambahkan: ' . implode(', ', $created));
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        $name = $permission->name;

        // Detach from all roles first
        $permission->roles()->detach();
        $permission->delete();

        return back()->with('success', "Permission '{$name}' berhasil dihapus.");
    }

    /**
     * Bulk delete permissions.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:permissions,id'],
        ]);

        $count = 0;
        foreach ($validated['ids'] as $id) {
            $permission = Permission::find($id);
            if ($permission) {
                $permission->roles()->detach();
                $permission->delete();
                $count++;
            }
        }

        return back()->with('success', "{$count} permission berhasil dihapus.");
    }
}
