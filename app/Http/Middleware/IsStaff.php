<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsStaff
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $isStaff = $user->isAdmin() || ($user->isEmployee() && $user->employee()->exists());

        abort_unless($isStaff, 403);

        return $next($request);
    }
}
