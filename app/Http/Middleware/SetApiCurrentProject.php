<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Support\CurrentProject;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiCurrentProject
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->header('X-Project-Id');
        if ($raw === null || $raw === '') {
            return response()->json(['message' => 'Missing X-Project-Id header.'], 422);
        }

        $id = (int) $raw;
        if ($id < 1 || ! Project::query()->listed()->whereKey($id)->exists()) {
            return response()->json(['message' => 'Invalid or inactive project.'], 422);
        }

        app(CurrentProject::class)->force($id);

        return $next($request);
    }
}
