<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestOrTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $role = auth()->user()->role;
            if ($role === 'super_admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($role === 'pemilik_kos') {
                return redirect()->route('owner.dashboard');
            }
        }

        return $next($request);
    }
}
