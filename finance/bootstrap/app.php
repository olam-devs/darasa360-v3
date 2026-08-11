<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureCanEditHistory;
use App\Http\Middleware\EnsureHeadmasterTenantContext;
use App\Http\Middleware\EnsureMainAccountant;
use App\Http\Middleware\EnsureParentPortalTenantContext;
use App\Http\Middleware\EnsureSchoolFeatureEnabled;
use App\Http\Middleware\EnsureTenantContext;
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
            'is.main.accountant' => EnsureMainAccountant::class,
            'finance.portal' => \App\Http\Middleware\EnsureFinancePortalAccess::class,
            'portal.session' => \App\Http\Middleware\EnsurePortalSession::class,
            'tenant.context' => EnsureTenantContext::class,
            'school.feature' => EnsureSchoolFeatureEnabled::class,
            'parent.tenant.context' => EnsureParentPortalTenantContext::class,
            'headmaster.tenant.context' => EnsureHeadmasterTenantContext::class,
        ]);

        $middleware->appendToGroup('web', [
            RedirectLocalhostTo127::class,
        ]);

        // External webhooks (NextSMS delivery reports) arrive with no
        // session, so they can never carry a CSRF token - exempt explicitly
        // rather than relying on auth/session state to fail open.
        $middleware->validateCsrfTokens(except: [
            'api/sms/delivery-callback',
        ]);

        // Route-model binding (SubstituteBindings) is the LAST middleware in
        // Laravel's default 'web' group, so without this it resolves
        // {student}/{headmaster}/{class}/etc. route params BEFORE our
        // tenant-switching middleware ever runs - silently reading from
        // whichever database the 'tenant' connection defaults to (this
        // environment's central DB) instead of the actual school's tenant
        // DB. Writes still landed correctly (Eloquent re-resolves the
        // connection at save time, after switching), which is what made
        // this go unnoticed - but any route using implicit model binding
        // was reading stale/wrong data. Force our middleware to run first.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: EnsureTenantContext::class,
        );
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: EnsureParentPortalTenantContext::class,
        );
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: EnsureHeadmasterTenantContext::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
