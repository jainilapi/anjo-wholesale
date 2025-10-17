<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionGate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->roles->where('slug', 'admin')->count()) {
            return $next($request);
        }

        if ($user->status != 1) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['inactive' => 'Your account is inactive.']);
        }

        if ($permission && !$user->can($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
