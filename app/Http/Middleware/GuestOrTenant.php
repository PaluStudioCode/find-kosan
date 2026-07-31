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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $role = auth()->user()->role;
            // Izinkan Admin dan Owner untuk melihat halaman detail kos publik
            if (($role === 'super_admin' || $role === 'admin') && $request->routeIs('public.kos.show')) {
                return $next($request);
            }
            if ($role === 'super_admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
        }

        return $next($request);
    }
}
