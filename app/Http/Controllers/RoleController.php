<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->get();

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return Inertia::render('Roles/Form', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['required', 'array', 'min:1'],
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    /**
     * Show form for editing a role.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return Inertia::render('Roles/Form', [
            'role' => $role->load('permissions'),
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        // Prevent editing system roles
        if (in_array($role->name, ['akademik'])) {
            return back()->with('error', 'Role sistem tidak dapat diubah.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,' . $role->id],
            'permissions' => ['required', 'array', 'min:1'],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting system roles
        if (in_array($role->name, ['akademik', 'staff_prodi', 'dosen', 'mahasiswa'])) {
            return back()->with('error', 'Role sistem tidak dapat dihapus.');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Role masih memiliki user, tidak dapat dihapus.');
        }

        $role->delete();

        return back()->with('success', 'Role berhasil dihapus.');
    }
}
