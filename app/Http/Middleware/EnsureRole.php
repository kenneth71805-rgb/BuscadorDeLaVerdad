<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('role:admin') or 'role:admin,editor_in_chief' */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $r) => Role::from($r), $roles);
        $user = $request->user();

        abort_unless($user && $user->hasRole(...$allowed), 403);

        return $next($request);
    }
}
