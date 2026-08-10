<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParentAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!\Illuminate\Support\Facades\Session::has('parent_student_id')) {
            $schoolSlug = session('parent_school_slug');
            return redirect()->route('parent.login', $schoolSlug ? ['schoolSlug' => $schoolSlug] : [])
                ->with('error', 'Please login to access the parent portal.');
        }
        return $next($request);
    }
}
