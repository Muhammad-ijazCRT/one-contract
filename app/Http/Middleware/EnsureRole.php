<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->is('admin', 'admin/*') && ! $request->is('admin/login')) {
                return redirect()->route('admin.login');
            }

            return redirect()->route('login');
        }

        $allowed = collect($roles)->map(fn (string $role) => UserRole::from($role));

        if (! $allowed->contains($user->role)) {
            $dashboardRoute = $user->role === UserRole::Admin ? route('admin.dashboard') : route('user.dashboard');

            return redirect($dashboardRoute)->with('error', 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
