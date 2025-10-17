<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfNotOperator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'operator')
    {
        if (!Auth::guard($guard)->check()) {
            \Log::info('Operator middleware: Not authenticated', [
                'url' => $request->url(),
                'guard' => $guard,
                'session_id' => session()->getId()
            ]);
            return redirect()->route('operator.login');
        }

        \Log::info('Operator middleware: Authenticated', [
            'url' => $request->url(),
            'operator_id' => Auth::guard($guard)->id(),
            'operator_email' => Auth::guard($guard)->user()->email
        ]);

        return $next($request);
    }
}
