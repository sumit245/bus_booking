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
        \Log::info('Authenticate middleware - checking', [
            'url' => $request->fullUrl(),
            'guards' => $guards,
            'has_guards' => !empty($guards)
        ]);
        
        // If guards are specified, check each one
        if (!empty($guards)) {
            foreach ($guards as $guard) {
                $isAuthenticated = Auth::guard($guard)->check();
                \Log::info('Authenticate middleware - checking guard', [
                    'guard' => $guard,
                    'is_authenticated' => $isAuthenticated
                ]);
                
                if ($isAuthenticated) {
                    \Log::info('Authenticate middleware - guard authenticated, proceeding', ['guard' => $guard]);
                    return $next($request);
                }
            }

            // If no guards are authenticated, redirect based on the first guard
            $firstGuard = $guards[0];
            \Log::warning('Authenticate middleware - no guards authenticated, redirecting', ['first_guard' => $firstGuard]);
            
            if ($firstGuard === 'admin') {
                return redirect()->route('admin.login');
            } elseif ($firstGuard === 'operator') {
                return redirect()->route('operator.login');
            } elseif ($firstGuard === 'agent') {
                // Redirect unauthenticated agent users to the agent login page
                return redirect()->route('agent.login');
            }

            // Fallback to the regular user login route
            return redirect()->route('user.login');
        }

        // If no guards specified, check default guard
        $defaultCheck = Auth::check();
        \Log::info('Authenticate middleware - default guard check', ['is_authenticated' => $defaultCheck]);
        
        if ($defaultCheck) {
            return $next($request);
        }
        
        \Log::warning('Authenticate middleware - default guard not authenticated, redirecting to user login');
        return redirect()->route('user.login');
    }

}
