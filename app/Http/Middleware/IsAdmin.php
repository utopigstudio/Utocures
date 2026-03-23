<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->employee()->exists()) {
            if (!$request->routeIs('dashboard')) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
