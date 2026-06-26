<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureCanEditHistory;
use App\Http\Middleware\HeadmasterAuthMiddleware;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\ParentAuthMiddleware;
use App\Http\Middleware\RedirectLocalhostTo127;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/superadmin.php'));
            Route::middleware('web')
                ->group(base_path('routes/headmaster.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('superadmin*')) {
                return route('superadmin.login');
            }
            return route('login');
        });

        $middleware->alias([
            'check.role' => CheckRole::class,
            'parent.auth' => ParentAuthMiddleware::class,
            'tenant' => IdentifyTenant::class,
            'superadmin' => SuperAdminMiddleware::class,
            'headmaster.auth' => HeadmasterAuthMiddleware::class,
            'can.edit.history' => EnsureCanEditHistory::class,
            'finance.portal' => \App\Http\Middleware\EnsureFinancePortalAccess::class,
            'portal.session' => \App\Http\Middleware\EnsurePortalSession::class,
        ]);

        $middleware->appendToGroup('web', [
            RedirectLocalhostTo127::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
