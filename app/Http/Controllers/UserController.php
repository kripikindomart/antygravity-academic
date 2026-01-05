<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Dosen;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'prodis', 'dosen'])
            ->when($request->tab === 'trash', function ($q) {
                $q->onlyTrashed();
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        // Search in Dosen relation
                        ->orWhereHas('dosen', function ($q) use ($search) {
                            $q->where('nip', 'like', "%{$search}%")
                                ->orWhere('nidn', 'like', "%{$search}%");
                        })
                        // Search in Mahasiswa relation
                        ->orWhereHas('mahasiswa', function ($q) use ($search) {
                            $q->where('nim', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->role, function ($q, $role) {
                $q->whereHas('roles', fn($q) => $q->where('name', $role));
            })
            ->when($request->status !== null && $request->tab !== 'trash', function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            })
            ->when($request->prodi, function ($q, $prodi) {
                $q->whereHas('prodis', fn($q) => $q->where('program_studis.id', $prodi));
            });

        $users = $query->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        // Get counts for tabs and roles
        $counts = [
            'active' => User::count(),
            'trash' => User::onlyTrashed()->count(),
        ];

        // Get stats by role
        $roleStats = [];
        foreach (Role::all() as $role) {
            $roleStats[$role->name] = User::role($role->name)->count();
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'role', 'status', 'sort', 'order', 'tab', 'prodi']),
            'counts' => $counts,
            'roleStats' => $roleStats,
            'prodis' => ProgramStudi::select('id', 'nama', 'kode')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Show form for creating a new user.
     */
    public function create()
    {
        // Get dosens that don't have a user account yet
        $availableDosens = Dosen::whereNull('user_id')
            ->orderBy('nama')
            ->get(['id', 'nama', 'nidn', 'nip']);

        return Inertia::render('Users/Form', [
            'roles' => Role::all(),
            'availableDosens' => $availableDosens,
            'prodis' => ProgramStudi::select('id', 'nama', 'kode')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'prodis' => ['nullable', 'array'],
            'dosen_id' => ['nullable', 'exists:dosens,id'],
            'avatar' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        if ($request->hasFile('avatar')) {
            $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create($userData);

        $user->syncRoles($validated['roles']);

        if (isset($validated['prodis'])) {
            $user->prodis()->sync($validated['prodis']);
        }

        // Link to Dosen if selected
        if (!empty($validated['dosen_id'])) {
            Dosen::where('id', $validated['dosen_id'])->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Show form for editing a user.
     */
    public function edit(User $user)
    {
        // Load user with dosen relationship
        $user->load(['roles', 'dosen', 'prodis']);

        // Get dosens that don't have a user account yet, OR the current user's dosen
        $availableDosens = Dosen::where(function ($q) use ($user) {
            $q->whereNull('user_id')
                ->orWhere('user_id', $user->id);
        })
            ->orderBy('nama')
            ->get(['id', 'nama', 'nidn', 'nip', 'user_id']);

        return Inertia::render('Users/Form', [
            'user' => $user,
            'roles' => Role::all(),
            'availableDosens' => $availableDosens,
            'prodis' => ProgramStudi::select('id', 'nama', 'kode')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'prodis' => ['nullable', 'array'],
            'dosen_id' => ['nullable', 'exists:dosens,id'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($updateData);
        $user->syncRoles($validated['roles']);

        if (isset($validated['prodis'])) {
            $user->prodis()->sync($validated['prodis']);
        }

        // Handle Dosen linking/unlinking
        // First, unlink any existing dosen
        Dosen::where('user_id', $user->id)->update(['user_id' => null]);

        // Then link the new one if selected
        if (!empty($validated['dosen_id'])) {
            Dosen::where('id', $validated['dosen_id'])->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified user (soft delete).
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Reset user password by admin.
     */
    public function resetPassword(Request $request, User $user)
    {
        // If manual password provided
        if ($request->password) {
            $validated = $request->validate([
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                ],
            ]);

            $user->update(['password' => Hash::make($validated['password'])]);

            return back()->with('success', 'Password berhasil diubah secara manual.');
        }

        // Default: Generate random
        $newPassword = Str::random(10);
        $user->update(['password' => Hash::make($newPassword)]);

        return back()->with('success', 'Password berhasil direset. Password baru: ' . $newPassword)
            ->with('new_password', $newPassword);
    }

    /**
     * Export users to Excel.
     */
    public function export(Request $request)
    {
        // Apply filters to export same data as viewed
        $query = User::with(['roles', 'prodis']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->status) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->get();

        return Excel::download(new UsersExport($users), 'users-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Bulk delete users.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:users,id'],
        ]);

        $ids = collect($validated['ids'])->filter(fn($id) => $id !== auth()->id());

        User::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' user berhasil dihapus.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', 'Status user berhasil diubah.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('success', 'User berhasil dipulihkan.');
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->forceDelete();

        return back()->with('success', 'User berhasil dihapus permanen.');
    }

    /**
     * Bulk restore users.
     */
    public function bulkRestore(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
        ]);

        User::onlyTrashed()->whereIn('id', $validated['ids'])->restore();

        return back()->with('success', count($validated['ids']) . ' user berhasil dipulihkan.');
    }

    /**
     * Bulk force delete users.
     */
    public function bulkForceDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
        ]);

        User::onlyTrashed()->whereIn('id', $validated['ids'])->forceDelete();

        return back()->with('success', count($validated['ids']) . ' user berhasil dihapus permanen.');
    }
}

