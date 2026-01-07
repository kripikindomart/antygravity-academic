<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Menu;
use App\Models\User;

try {
    echo "Checking current menus:\n";
    $roots = Menu::whereNull('parent_id')->orderBy('order')->get();
    foreach ($roots as $root) {
        echo "- [{$root->id}] {$root->name} (Section: {$root->section}, Permission: {$root->permission})\n";
        foreach ($root->children as $child) {
            echo "  -- [{$child->id}] {$child->name} (Route: {$child->route}, Permission: {$child->permission})\n";
        }
    }

    echo "\nDeleting existing 'Survei EDOM' menus to prevent duplicates or structure errors...\n";
    $existing = Menu::where('name', 'Survei EDOM')->first();
    if ($existing) {
        Menu::where('parent_id', $existing->id)->delete();
        $existing->delete();
        echo "Deleted old 'Survei EDOM' menu.\n";
    }

    echo "Re-inserting Survei EDOM menu correctly...\n";

    // Create SECTION menu (root)
    // IMPORTANT: Based on Menu model, 'section' field might be used for grouping in sidebar.
    // Check HandleInertiaRequests.php logic (waiting for view_file result).
    // Assuming 'AKADEMIK' is a valid section based on standard app structure.

    $parent = Menu::create([
        'name' => 'Survei EDOM',
        'icon' => 'ClipboardDocumentCheckIcon',
        'route' => '#',
        'order' => 99, // High order to appear at bottom or adjust
        'section' => 'Akademik', // Merges into existing Akademik section
        'parent_id' => null,
        'permission' => 'survey.view',
        'is_active' => true,
    ]);

    $children = [
        ['name' => 'Dashboard', 'route' => 'survey.dashboard', 'icon' => 'ChartBarIcon', 'permission' => 'survey.view'],
        ['name' => 'Template Survei', 'route' => 'survey.templates.index', 'icon' => 'DocumentTextIcon', 'permission' => 'survey.manage'],
        ['name' => 'Periode Survei', 'route' => 'survey.periods.index', 'icon' => 'CalendarDaysIcon', 'permission' => 'survey.manage'],
        ['name' => 'Isi Survei', 'route' => 'survey.responses.index', 'icon' => 'PencilSquareIcon', 'permission' => null],
    ];

    foreach ($children as $idx => $child) {
        Menu::create([
            'name' => $child['name'],
            'route' => $child['route'],
            'icon' => $child['icon'],
            'order' => $idx + 1,
            'parent_id' => $parent->id,
            'permission' => $child['permission'],
            'section' => 'Akademik', // Inherit section
            'is_active' => true,
        ]);
    }

    echo "Success. New ID: {$parent->id}\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
