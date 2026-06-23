<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    View::composer('*', function ($view) {
      $role = session('role', 'guest');
      $package = session('package', null); // core, standard, premium

      $menuFiles = [
        'SuperAdmin' => base_path('resources/menu/verticalMenuSuperAdmin.json'),
        'SystemAdmin' => base_path('resources/menu/verticalMenuSystemAdmin.json'),
        'Admin' => base_path('resources/menu/verticalMenuSchoolAdmin.json'),
        'Accountant' => base_path('resources/menu/verticalMenuAccountant.json'),
        'Owner' => base_path('resources/menu/verticalMenuOwner.json'),
        'Headmaster' => base_path('resources/menu/verticalMenuHeadTeacher.json'),
        'ClassTeacher' => base_path('resources/menu/verticalMenuClassTeacher.json'),
        'Teacher' => base_path('resources/menu/verticalMenuTeacher.json'),
        'Academic' => base_path('resources/menu/verticalMenuAcademic.json'),
        'Student' => base_path('resources/menu/verticalMenuStudent.json'),
        'guest' => base_path('resources/menu/verticalMenuAdmin.json'),
      ];

      $menuFile = $menuFiles[$role] ?? $menuFiles['guest'];
      $menuJson = file_get_contents($menuFile);
      $menuData = json_decode($menuJson);

      // Define package inheritance levels
      $packageHierarchy = [
        'core' => ['core'],
        'standard' => ['core', 'standard'],
        'premium' => ['core', 'standard', 'premium'],
      ];

      $allowedPackages = $packageHierarchy[strtolower($package)] ?? ['core', 'standard', 'premium'];

      $filterByPackage = function ($items) use (&$filterByPackage, $allowedPackages) {
        $filtered = [];

        foreach ($items as $item) {
          $itemPackages = [];

          if (isset($item->package)) {
            if (is_array($item->package)) {
              $itemPackages = $item->package;
            } elseif (is_string($item->package)) {
              $itemPackages = [$item->package];
            }
          }

          // If no package restriction, or current package has access
          if (empty($itemPackages) || array_intersect($allowedPackages, $itemPackages)) {
            if (isset($item->submenu) && is_array($item->submenu)) {
              $item->submenu = $filterByPackage($item->submenu);
            }
            $filtered[] = $item;
          }
        }

        return $filtered;
      };

      if (isset($menuData->menu) && is_array($menuData->menu)) {
        $menuData->menu = $filterByPackage($menuData->menu);
      }

      $view->with('menuData', [$menuData]);
    });
  }
}
