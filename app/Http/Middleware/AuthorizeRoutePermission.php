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
            if (! config('permissions.enforce_route_map', false)) {
                return $next($request);
            }

            if (app()->isLocal()) {
                throw new \RuntimeException('Route has no name; cannot authorize permission.');
            }

            abort(403, 'تعذّر التحقق من الصلاحيات لهذا المسار.');
        }

        /** @var array<string, string> $map */
        $map = config('route-permissions', []);
        if (! array_key_exists($name, $map)) {
            if (! config('permissions.enforce_route_map', false)) {
                return $next($request);
            }

            if (app()->isLocal()) {
                throw new \RuntimeException("Missing route permission mapping for route: {$name}");
            }

            abort(403, 'صلاحية هذا المسار غير مُعرّفة.');
        }

        $slug = $map[$name];
        if (! $user->can($slug)) {
            abort(403, 'ليس لديك صلاحية لتنفيذ هذا الإجراء.');
        }

        return $next($request);
    }
}
