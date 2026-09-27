<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WebsiteProject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebsiteProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->listed()
            ->with(['websiteProject'])
            ->withCount(['properties', 'areas'])
            ->orderBy('name')
            ->get();

        return view('website.projects.index', [
            'title' => 'مشاريع الموقع | Mohaseb Aqary',
            'pageTitle' => 'مشاريع الموقع',
            'projects' => $projects,
        ]);
    }

    public function edit(Project $project): View
    {
        abort_unless($project->is_active && ! $project->is_draft && ! $project->is_land_trading_cashbox, 404);

        $website = WebsiteProject::query()->firstOrNew(
            ['project_id' => $project->id],
            [
                'title' => $project->name,
                'is_published' => false,
                'show_on_home' => true,
                'sort_order' => 0,
            ]
        );

        return view('website.projects.edit', [
            'title' => 'تعديل عرض المشروع | الموقع',
            'pageTitle' => 'عرض المشروع على الموقع',
            'project' => $project,
            'website' => $website,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->is_active && ! $project->is_draft && ! $project->is_land_trading_cashbox, 404);

        $data = $request->validate([
            'is_published' => ['nullable', 'boolean'],
            'show_on_home' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'title' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:160'],
            'status_label' => ['nullable', 'string', 'max:80'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'features_text' => ['nullable', 'string', 'max:3000'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'gallery.*' => ['nullable', 'image', 'max:5120'],
            'remove_cover' => ['nullable', 'boolean'],
            'remove_gallery' => ['nullable', 'array'],
            'remove_gallery.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $website = WebsiteProject::query()->firstOrNew(['project_id' => $project->id]);
        $disk = Storage::disk('public');
        $dir = 'website/projects/'.$project->id;

        $gallery = is_array($website->gallery) ? array_values($website->gallery) : [];

        if ($request->boolean('remove_cover') && $website->cover_path) {
            $disk->delete($website->cover_path);
            $website->cover_path = null;
        }

        $removeIndexes = collect($request->input('remove_gallery', []))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->sortDesc()
            ->values();

        foreach ($removeIndexes as $idx) {
            if (! isset($gallery[$idx])) {
                continue;
            }
            $disk->delete($gallery[$idx]);
            unset($gallery[$idx]);
        }
        $gallery = array_values($gallery);

        if ($request->hasFile('cover')) {
            if ($website->cover_path) {
                $disk->delete($website->cover_path);
            }
            $website->cover_path = $request->file('cover')->store($dir.'/cover', 'public');
        }

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                if (! $file) {
                    continue;
                }
                $gallery[] = $file->store($dir.'/gallery', 'public');
            }
        }

        $features = preg_split('/\r\n|\r|\n/', (string) ($data['features_text'] ?? '')) ?: [];
        $features = array_values(array_filter(array_map('trim', $features), fn ($v) => $v !== ''));

        $website->fill([
            'is_published' => $request->boolean('is_published'),
            'show_on_home' => $request->boolean('show_on_home'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'location' => $data['location'] ?? null,
            'status_label' => $data['status_label'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'description' => $data['description'] ?? null,
            'cta_label' => $data['cta_label'] ?? null,
            'features' => $features,
            'gallery' => $gallery,
        ]);
        $website->project_id = $project->id;
        $website->save();

        return redirect()
            ->route('website.projects.edit', $project)
            ->with('success', 'تم حفظ عرض المشروع على الموقع.');
    }

    public function quickToggle(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->is_active && ! $project->is_draft && ! $project->is_land_trading_cashbox, 404);

        $website = WebsiteProject::query()->firstOrCreate(
            ['project_id' => $project->id],
            [
                'title' => $project->name,
                'show_on_home' => true,
                'sort_order' => 0,
            ]
        );

        $website->is_published = ! $website->is_published;
        if ($website->title === null || trim((string) $website->title) === '') {
            $website->title = $project->name;
        }
        $website->save();

        return back()->with('success', $website->is_published
            ? 'تم نشر المشروع على الموقع.'
            : 'تم إخفاء المشروع من الموقع.');
    }
}
