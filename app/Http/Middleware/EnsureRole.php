<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow only active users with the given role. An account that has been
     * deactivated since it logged in is signed out on its next request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        abort_unless($user->role === UserRole::from($role), 403);

        if ($user->role === UserRole::Affiliate) {
            abort_unless($user->affiliate !== null, 403);
        }

        return $next($request);
    }
}
