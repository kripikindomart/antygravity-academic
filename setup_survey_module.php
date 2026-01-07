<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Menu;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

try {
    echo "=== Survey EDOM Menu & Permission Setup ===\n\n";

    // 1. Create permissions
    echo "1. Creating permissions...\n";
    $perms = ['survey.view', 'survey.manage'];
    foreach ($perms as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        echo "   - {$p} OK\n";
    }

    // 2. Assign to roles
    echo "\n2. Assigning permissions to roles...\n";
    $rolePerms = [
        'administrator' => ['survey.view', 'survey.manage'],
        'akademik' => ['survey.view', 'survey.manage'],
        'kaprodi' => ['survey.view'],
        'dosen' => ['survey.view'],
    ];
    foreach ($rolePerms as $roleName => $permissions) {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $role->givePermissionTo($permissions);
            echo "   - {$roleName}: " . implode(', ', $permissions) . "\n";
        }
    }

    // 3. Clean old survey menus
    echo "\n3. Cleaning old Survey menus...\n";
    $old = Menu::where('name', 'like', '%Survei%')->orWhere('name', 'like', '%Survey%')->get();
    foreach ($old as $m) {
        Menu::where('parent_id', $m->id)->delete();
        $m->delete();
        echo "   - Deleted: {$m->name}\n";
    }

    // 4. Create new menu structure
    echo "\n4. Creating Survey EDOM menu...\n";
    $parent = Menu::create([
        'name' => 'Survei EDOM',
        'slug' => 'survey-edom',
        'icon' => 'ClipboardDocumentCheckIcon',
        'href' => null,
        'route' => null,
        'permission' => 'survey.view',
        'parent_id' => null,
        'section' => 'Akademik',
        'order' => 50,
        'is_active' => true,
    ]);
    echo "   - Parent created: ID {$parent->id}\n";

    $children = [
        ['name' => 'Dashboard', 'route' => 'survey.dashboard', 'icon' => 'ChartBarIcon', 'permission' => 'survey.view', 'order' => 1],
        ['name' => 'Template Survei', 'route' => 'survey.templates.index', 'icon' => 'DocumentTextIcon', 'permission' => 'survey.manage', 'order' => 2],
        ['name' => 'Periode Survei', 'route' => 'survey.periods.index', 'icon' => 'CalendarDaysIcon', 'permission' => 'survey.manage', 'order' => 3],
        ['name' => 'Isi Survei', 'route' => 'survey.responses.index', 'icon' => 'PencilSquareIcon', 'permission' => null, 'order' => 4],
    ];

    foreach ($children as $c) {
        Menu::create([
            'name' => $c['name'],
            'slug' => \Str::slug($c['name']),
            'icon' => $c['icon'],
            'href' => null,
            'route' => $c['route'],
            'permission' => $c['permission'],
            'parent_id' => $parent->id,
            'section' => 'Akademik',
            'order' => $c['order'],
            'is_active' => true,
        ]);
        echo "   - Child created: {$c['name']}\n";
    }

    // 5. Clear caches
    echo "\n5. Clearing caches...\n";
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    \Artisan::call('cache:clear');
    echo "   - Cache cleared\n";

    echo "\n=== DONE ===\n";
    echo "Refresh browser and check sidebar for 'Survei EDOM' menu.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
