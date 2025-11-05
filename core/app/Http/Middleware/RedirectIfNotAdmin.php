<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
class RedirectIfNotAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'admin')
    {
        \Log::info('RedirectIfNotAdmin middleware - checking auth', [
            'url' => $request->fullUrl(),
            'guard' => $guard,
            'is_authenticated' => Auth::guard($guard)->check(),
            'user_id' => Auth::guard($guard)->id() ?? null
        ]);
        
        if (!Auth::guard($guard)->check()) {
            \Log::warning('RedirectIfNotAdmin - User not authenticated, redirecting to login');
            return redirect()->route('admin.login');
        }

        \Log::info('RedirectIfNotAdmin middleware - User authenticated, proceeding');
        return $next($request);
    }
}
