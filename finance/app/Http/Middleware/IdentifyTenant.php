<?php

namespace App\Http\Middleware;

use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    protected TenantDatabaseManager $tenantManager;

    public function __construct(TenantDatabaseManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the identifier from subdomain or path
        $identifier = $this->getIdentifier($request);

        // If this is a super admin request, use central database
        if ($identifier === 'superadmin') {
            config(['database.default' => 'central']);
            return $next($request);
        }

        // Identify the tenant
        $school = $this->tenantManager->identifyTenant($identifier);

        if (!$school) {
            abort(404, 'School not found or inactive');
        }

        // Check if subscription is expired
        if ($school->isSubscriptionExpired()) {
            abort(403, 'School subscription has expired. Please contact support.');
        }

        // Switch to the school's database
        $this->tenantManager->switchToSchool($school);

        return $next($request);
    }

    /**
     * Get the tenant identifier from the request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getIdentifier(Request $request): ?string
    {
        // Try to get from subdomain first
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // If we have a subdomain (e.g., school1.darasafinance.com)
        if (count($parts) >= 3) {
            return $parts[0]; // Return the subdomain
        }

        // Try to get from path (e.g., /school1/...)
        $path = $request->path();
        $pathParts = explode('/', $path);
        
        if (count($pathParts) > 0 && $pathParts[0] !== '') {
            // Check if first path segment is a valid school slug
            return $pathParts[0];
        }

        // Try to get from session (for direct login)
        if ($request->session()->has('current_school_slug')) {
            return $request->session()->get('current_school_slug');
        }

        return null;
    }
}
