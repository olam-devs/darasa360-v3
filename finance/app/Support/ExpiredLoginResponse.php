<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExpiredLoginResponse
{
    public function __invoke(Response $response, Throwable $exception, Request $request): Response
    {
        if ($response->getStatusCode() !== 419 || !$request->isMethod('POST')
            || !$request->is('login') || $request->expectsJson()) {
            return $response;
        }

        // CSRF rejection remains enforced. Never replay a login or retain its password.
        return redirect()->route('login')
            ->with('status', 'Your sign-in page expired. Please enter your password again to continue.')
            ->withInput($request->only('email'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
