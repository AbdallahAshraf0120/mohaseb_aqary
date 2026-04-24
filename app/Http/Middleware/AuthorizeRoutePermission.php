<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $name = $request->route()?->getName();
        if ($name === null) {
            return $next($request);
        }

        /** @var array<string, string> $map */
        $map = config('route-permissions', []);
        if (! array_key_exists($name, $map)) {
            return $next($request);
        }

        $slug = $map[$name];
        if (! $user->can($slug)) {
            abort(403, 'ليس لديك صلاحية لتنفيذ هذا الإجراء.');
        }

        return $next($request);
    }
}
