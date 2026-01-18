<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && $user->suspended) {
            // If the user is suspended and not already on the suspended page, redirect them.
            if (!$request->routeIs('account.suspended') && !$request->routeIs('auth.logout')) {
                return redirect()->route('account.suspended');
            }
        }

        // If user is NOT suspended but tries to access the suspended page, redirect to home.
        if ($user && !$user->suspended && $request->routeIs('account.suspended')) {
            return redirect('/');
        }

        return $next($request);
    }
}
