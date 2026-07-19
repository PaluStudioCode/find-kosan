<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustChangePasswordMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->must_change_password) {
            // Allow them to access profile and password update pages
            if (! $request->routeIs('profile.*') && ! $request->routeIs('password.*') && ! $request->routeIs('logout')) {
                return redirect()->route('profile.edit')->with('status', 'Anda wajib mengganti password awal sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
