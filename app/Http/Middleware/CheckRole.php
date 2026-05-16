<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user->loadMissing('role');

        $userRoleSlug = $user->role?->slug;

        foreach ($roles as $role) {
            if ($userRoleSlug === $role) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden: insufficient role'], 403);
    }
}
