<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HeadmasterAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $schoolSlug = session('headmaster_school_slug');
        $loginRoute = fn () => route('headmaster.login', $schoolSlug ? ['schoolSlug' => $schoolSlug] : []);

        if (!session('headmaster_id')) {
            return redirect($loginRoute())
                ->with('error', 'Please login to access the headmaster portal');
        }

        // Get headmaster from session
        $headmaster = \App\Models\Headmaster::find(session('headmaster_id'));

        if (!$headmaster || !$headmaster->is_active) {
            session()->forget('headmaster_id');
            return redirect($loginRoute())
                ->with('error', 'Your account is inactive. Please contact the school accountant.');
        }

        // Make headmaster available in views
        view()->share('headmaster', $headmaster);

        return $next($request);
    }
}
