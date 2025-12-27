<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuController extends Controller
{
    /**
     * Display menu management page
     */
    public function index()
    {
        $menus = Menu::with(['children', 'roles'])
            ->root()
            ->orderBy('section')
            ->orderBy('order')
            ->get();

        $roles = Role::all();
        $permissions = Permission::orderBy('name')->pluck('name');

        // Group menus by section
        $groupedMenus = $menus->groupBy('section');

        return Inertia::render('Menus/Index', [
            'menus' => $menus,
            'groupedMenus' => $groupedMenus,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a new menu
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:menus,slug'],
            'icon' => ['nullable', 'string', 'max:50'],
            'href' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string', 'exists:permissions,name'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'section' => ['nullable', 'string', 'max:100'],
            'order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $menu = Menu::create($validated);

        return back()->with('success', 'Menu berhasil ditambahkan.');
    }

    /**
     * Update a menu
     */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:menus,slug,' . $menu->id],
            'icon' => ['nullable', 'string', 'max:50'],
            'href' => ['nullable', 'string', 'max:255'],
            'permission' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'section' => ['nullable', 'string', 'max:100'],
            'order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        // Prevent menu from being its own parent
        if (($validated['parent_id'] ?? null) == $menu->id) {
            return back()->withErrors(['parent_id' => 'Menu cannot be its own parent.']);
        }

        $menu->update($validated);

        return back()->with('success', 'Menu berhasil diperbarui.');
    }

    /**
     * Delete a menu
     */
    public function destroy(Menu $menu)
    {
        // Move children to parent before deleting
        Menu::where('parent_id', $menu->id)->update(['parent_id' => $menu->parent_id]);

        $menu->delete();

        return back()->with('success', 'Menu berhasil dihapus.');
    }

    /**
     * Update menu order
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:menus,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
            'items.*.parent_id' => ['nullable', 'exists:menus,id'],
        ]);

        foreach ($validated['items'] as $item) {
            Menu::where('id', $item['id'])->update([
                'order' => $item['order'],
                'parent_id' => $item['parent_id'] ?? null,
            ]);
        }

        return back()->with('success', 'Urutan menu berhasil diperbarui.');
    }

    /**
     * Update role visibility for a menu
     */
    public function updateRoleVisibility(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'is_visible' => ['required', 'boolean'],
        ]);

        $menu->roles()->syncWithoutDetaching([
            $validated['role_id'] => ['is_visible' => $validated['is_visible']]
        ]);

        return back()->with('success', 'Visibilitas role berhasil diperbarui.');
    }

    /**
     * Sync menus from current hardcoded sidebar
     */
    public function syncFromSidebar()
    {
        $defaultMenus = [
            // Menu Utama
            ['name' => 'Dashboard', 'slug' => 'dashboard', 'icon' => 'home', 'href' => '/dashboard', 'permission' => null, 'section' => 'Menu Utama', 'order' => 1],
            ['name' => 'Jadwal Kuliah', 'slug' => 'jadwal', 'icon' => 'calendar', 'href' => '/jadwal', 'permission' => 'jadwal.view', 'section' => 'Menu Utama', 'order' => 2],
            ['name' => 'Kurikulum OBE', 'slug' => 'kurikulum', 'icon' => 'academic', 'href' => '/kurikulum', 'permission' => 'kurikulum.view', 'section' => 'Menu Utama', 'order' => 3],

            // Akademik
            ['name' => 'Absensi', 'slug' => 'absensi', 'icon' => 'clipboard-check', 'href' => '/absensi', 'permission' => 'absensi.view', 'section' => 'Akademik', 'order' => 1],
            ['name' => 'Jurnal Perkuliahan', 'slug' => 'jurnal', 'icon' => 'document-text', 'href' => '/jurnal', 'permission' => 'jurnal.view', 'section' => 'Akademik', 'order' => 2],
            ['name' => 'Nilai', 'slug' => 'nilai', 'icon' => 'chart-bar', 'href' => '/nilai', 'permission' => 'nilai.view', 'section' => 'Akademik', 'order' => 3],
            ['name' => 'Survei Evaluasi', 'slug' => 'survei', 'icon' => 'star', 'href' => '/survei', 'permission' => 'survei.view', 'section' => 'Akademik', 'order' => 4],

            // Master Data
            ['name' => 'Program Studi', 'slug' => 'prodi', 'icon' => 'building', 'href' => '/prodi', 'permission' => 'prodi.view', 'section' => 'Master Data', 'order' => 1],
            ['name' => 'Ruangan', 'slug' => 'ruangan', 'icon' => 'location', 'href' => '/ruangan', 'permission' => 'ruangan.view', 'section' => 'Master Data', 'order' => 2],
            ['name' => 'Tahun Akademik', 'slug' => 'tahun-akademik', 'icon' => 'clock', 'href' => '/master/tahun-akademik', 'permission' => 'semester.create', 'section' => 'Master Data', 'order' => 3],
            ['name' => 'Mata Kuliah', 'slug' => 'mata-kuliah', 'icon' => 'book', 'href' => '/mata-kuliah', 'permission' => 'matakuliah.view', 'section' => 'Master Data', 'order' => 4],

            // Manajemen
            ['name' => 'Pengguna', 'slug' => 'users', 'icon' => 'users', 'href' => '/users', 'permission' => 'users.view', 'section' => 'Manajemen', 'order' => 1],
            ['name' => 'Roles & Permissions', 'slug' => 'roles', 'icon' => 'shield', 'href' => '/roles', 'permission' => 'roles.view', 'section' => 'Manajemen', 'order' => 2],
            ['name' => 'Menu', 'slug' => 'menus', 'icon' => 'menu', 'href' => '/menus', 'permission' => 'roles.view', 'section' => 'Manajemen', 'order' => 3],
            ['name' => 'Pengaturan', 'slug' => 'settings', 'icon' => 'cog', 'href' => '/settings', 'permission' => 'settings.view', 'section' => 'Manajemen', 'order' => 4],

            // Laporan
            ['name' => 'Laporan', 'slug' => 'laporan', 'icon' => 'document-report', 'href' => '/laporan', 'permission' => 'laporan.view', 'section' => 'Laporan', 'order' => 1],
            ['name' => 'Rekap Honorarium', 'slug' => 'honorarium', 'icon' => 'currency', 'href' => '/laporan/honorarium', 'permission' => 'absensi.rekap', 'section' => 'Laporan', 'order' => 2],
            ['name' => 'SK Mengajar', 'slug' => 'sk-mengajar', 'icon' => 'document', 'href' => '/sk-mengajar', 'permission' => 'sk_mengajar.view', 'section' => 'Laporan', 'order' => 3],
        ];

        foreach ($defaultMenus as $menuData) {
            Menu::updateOrCreate(
                ['slug' => $menuData['slug']],
                $menuData
            );
        }

        return back()->with('success', 'Menu berhasil disinkronkan dari sidebar.');
    }
}
