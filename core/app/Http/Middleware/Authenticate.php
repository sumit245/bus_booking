<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */

    public function handle($request, Closure $next, ...$guards)
    {
        // If guards are specified, check each one
        if (!empty($guards)) {
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    return $next($request);
                }
            }

            // If no guards are authenticated, redirect based on the first guard
            $firstGuard = $guards[0];
            if ($firstGuard === 'admin') {
                return redirect()->route('admin.login');
            } elseif ($firstGuard === 'operator') {
                return redirect()->route('operator.login');
            }
            return redirect()->route('user.login');
        }

        // If no guards specified, check default guard
        if (Auth::check()) {
            return $next($request);
        }
        return redirect()->route('user.login');
    }

}
