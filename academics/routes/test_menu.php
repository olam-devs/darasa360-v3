<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-menu-debug', function() {
    $role = 'Academic';
    $menuFile = base_path("resources/menu/verticalMenuAcademic.json");

    if (!file_exists($menuFile)) {
        return response()->json(['error' => 'Menu file not found', 'path' => $menuFile]);
    }

    $menuJson = file_get_contents($menuFile);
    $menuData = json_decode($menuJson, true);

    $reportCardsFound = false;
    $reportCardsData = null;
    $position = 0;

    foreach ($menuData['menu'] as $index => $item) {
        if (isset($item['name']) && $item['name'] === 'Report Cards') {
            $reportCardsFound = true;
            $reportCardsData = $item;
            $position = $index + 1;
            break;
        }
    }

    return response()->json([
        'status' => 'Menu Debug Information',
        'menu_file_path' => $menuFile,
        'menu_file_exists' => file_exists($menuFile),
        'menu_file_size' => filesize($menuFile) . ' bytes',
        'menu_file_modified' => date('Y-m-d H:i:s', filemtime($menuFile)),
        'total_menu_items' => count($menuData['menu']),
        'report_cards_found' => $reportCardsFound,
        'report_cards_position' => $position,
        'report_cards_data' => $reportCardsData,
        'all_menu_items' => array_map(function($item) {
            return $item['name'] ?? 'unnamed';
        }, $menuData['menu'])
    ], JSON_PRETTY_PRINT);
});
