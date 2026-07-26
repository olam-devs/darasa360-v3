<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
    then: function () {
      Route::middleware('web')->group(base_path('routes/super_admin_routes.php'));
      require base_path('routes/internal_api.php');
    },
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\InitializeTenantDatabase::class);
    $middleware->append(\App\Http\Middleware\LogRequests::class);
    $middleware->validateCsrfTokens(except: [
      'api/*'
    ]);

    // Register role middleware alias
    $middleware->alias([
      'role'        => \App\Http\Middleware\RoleMiddleware::class,
      'tenant.role' => \App\Http\Middleware\TenantRoleMiddleware::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })
  ->create();
