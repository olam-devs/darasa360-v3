<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckAuthOrToJson
{
  // public function handle($request, Closure $next)
  // {
  //   if ($request->is('login') || $request->routeIs('login') || $request->routeIs('login-page')) {
  //     return $next($request);
  //   }

  //   if (session()->has('user_id') || $request->has('toJson')) {
  //     return $next($request);
  //   }

  //   return  $next($request);
  // }

  public function handle($request, Closure $next)
  {

    Log::info('Session data:', session()->all());
    if ($request->is('login') || $request->routeIs('login') || $request->routeIs('login-page')) {
      return $next($request);
    }

    if (session()->has('role')) {
      return $next($request);
    } else {
      return redirect('/login');
    }
  }
}
