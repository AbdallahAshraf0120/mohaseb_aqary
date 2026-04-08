<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->role === 'admin') {
            return $next($request);
        }

        $rolePermissions = [
            'accountant' => ['view-properties', 'manage-accounting'],
            'sales' => ['view-properties', 'manage-sales'],
            'viewer' => ['view-properties'],
        ];

        $allowed = $rolePermissions[$user->role] ?? [];
        $hasPermission = collect($permissions)->every(fn (string $permission): bool => in_array($permission, $allowed, true));

        if (! $hasPermission) {
            abort(403, 'Unauthorized permission.');
        }

        return $next($request);
    }
}
