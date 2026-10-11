<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A member suspended by an admin is logged out at their next request.
 */
class LogoutSuspendedUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Votre compte est suspendu.'], 401)
                : redirect()->route('login')->withErrors(['email' => 'Votre compte a été suspendu par un administrateur.']);
        }

        return $next($request);
    }
}
