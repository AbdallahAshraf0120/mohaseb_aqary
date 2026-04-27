<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Support\CurrentProject;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SyncProjectFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if (! $project instanceof Project) {
            abort(404);
        }

        if ($project->is_draft) {
            return redirect()
                ->route('projects.index')
                ->with('error', 'هذا المشروع في المسودة. من صفحة «المشاريع» اضغط «إرجاع للقائمة» ثم افتح لوحة التحكم من الشريط الجانبي.');
        }

        session(['current_project_id' => (int) $project->id]);
        app(CurrentProject::class)->force((int) $project->id);
        URL::defaults(['project' => $project->getRouteKey()]);

        return $next($request);
    }
}
