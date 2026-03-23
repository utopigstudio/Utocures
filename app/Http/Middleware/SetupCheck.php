<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Models\User;

class SetupCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $hasConfig = Configuration::query()->exists();
        $hasUser = User::query()->exists();

        if ($request->is('setup*') && ($hasConfig && $hasUser)) {
            return redirect()->route('home');
        }

        if (!$request->is('setup*') && (!$hasConfig || !$hasUser)) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
