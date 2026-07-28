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
    // Must run in the 'web' group specifically (after StartSession), not the
    // bare global stack - plain append()/prepend() run before StartSession,
    // so session('school_db') always read null here and the tenant DB
    // connection was never actually switched for any real login. Every
    // school-level page (school-admin, teacher, headmaster, ...) was
    // silently hitting the unconfigured placeholder 'tenant' connection.
    $middleware->web(append: [
      \App\Http\Middleware\InitializeTenantDatabase::class,
      \App\Http\Middleware\LogRequests::class,
    ]);
    $middleware->validateCsrfTokens(except: [
      'api/*'
    ]);

    // Register role middleware alias
    $middleware->alias([
      'role'        => \App\Http\Middleware\RoleMiddleware::class,
      'tenant.role' => \App\Http\Middleware\TenantRoleMiddleware::class,
    ]);

    // SubstituteBindings (implicit route-model binding) sits at the end of
    // Laravel's default 'web' group, and InitializeTenantDatabase was only
    // appended after it with no explicit priority - so any route with a
    // bound tenant-connected model (e.g. AssignmentController::update()'s
    // `Assignment $assignment`, reachable from headmaster/owner/teacher
    // routes) resolved the model BEFORE the tenant DB was switched, using
    // whatever the 'tenant' connection defaults to instead of the real
    // school's database. Writes could still land on the right row only by
    // coincidence (Eloquent re-resolves the connection at save time, after
    // this middleware runs) - same bug class found and fixed in Finance's
    // bootstrap/app.php on 2026-07-28. Force tenant switching to run first.
    $middleware->prependToPriorityList(
      before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
      prepend: \App\Http\Middleware\InitializeTenantDatabase::class,
    );
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })
  ->create();
