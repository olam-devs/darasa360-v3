<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLocalhostTo127
{
    /**
     * Avoid CSRF/session cookie mismatch between localhost and 127.0.0.1 in local dev.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if ($host === 'localhost') {
            $target = 'http://127.0.0.1:8000'.$request->getRequestUri();

            return redirect()->to($target, 302);
        }

        return $next($request);
    }
}
