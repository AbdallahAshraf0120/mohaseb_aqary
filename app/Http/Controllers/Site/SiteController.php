<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\Project;
use App\Models\Property;
use App\Models\WebsiteProject;
use App\Support\WebsiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home(): View
    {
        $site = WebsiteContent::all();
        $this->ensurePublished($site);

        $limit = (int) ($site->home_projects_limit ?? 6);
        $projects = WebsiteProject::query()
            ->published()
            ->onHome()
            ->with(['project' => fn ($q) => $q->withCount(['properties', 'areas'])])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return view('site.home', [
            'title' => $site->brand.' | '.$site->tagline,
            'site' => $site,
            'projects' => $projects,
        ]);
    }

    public function projects(): View
    {
        $site = WebsiteContent::all();
        $this->ensurePublished($site);

        $projects = WebsiteProject::query()
            ->published()
            ->with(['project' => fn ($q) => $q->withCount(['properties', 'areas', 'sales'])])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('site.projects.index', [
            'title' => 'المشاريع | '.$site->brand,
            'site' => $site,
            'projects' => $projects,
        ]);
    }

    public function projectShow(Project $project): View
    {
        $site = WebsiteContent::all();
        $this->ensurePublished($site);

        $websiteProject = WebsiteProject::query()
            ->published()
            ->where('project_id', $project->id)
            ->with([
                'project.areas:id,project_id,name',
                'project.properties' => fn ($q) => $q
                    ->select([
                        'id',
                        'project_id',
                        'area_id',
                        'name',
                        'property_type',
                        'floors_count',
                        'total_apartments',
                        'location',
                        'status',
                    ])
                    ->orderBy('name'),
            ])
            ->firstOrFail();

        return view('site.projects.show', [
            'title' => $websiteProject->displayTitle().' | '.$site->brand,
            'site' => $site,
            'websiteProject' => $websiteProject,
            'project' => $websiteProject->project,
        ]);
    }

    public function about(): View
    {
        $site = WebsiteContent::all();
        $this->ensurePublished($site);

        $stats = [
            'projects' => WebsiteProject::query()->published()->count(),
            'properties' => Property::query()
                ->whereHas('project', fn ($q) => $q->listed()->whereHas('websiteProject', fn ($w) => $w->where('is_published', true)))
                ->count(),
        ];

        return view('site.about', [
            'title' => ($site->about_title ?: 'من نحن').' | '.$site->brand,
            'site' => $site,
            'stats' => $stats,
        ]);
    }

    public function contact(): View
    {
        $site = WebsiteContent::all();
        $this->ensurePublished($site);

        $projects = WebsiteProject::query()
            ->published()
            ->with('project:id,name')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('site.contact', [
            'title' => ($site->contact_title ?: 'تواصل معنا').' | '.$site->brand,
            'site' => $site,
            'projects' => $projects,
        ]);
    }

    public function contactStore(Request $request): RedirectResponse
    {
        $site = WebsiteContent::all();
        $this->ensurePublished($site);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'name' => 'الاسم',
            'phone' => 'رقم الهاتف',
            'email' => 'البريد',
            'project_id' => 'المشروع',
            'message' => 'الرسالة',
        ]);

        if (! empty($data['project_id'])) {
            $ok = WebsiteProject::query()->published()->where('project_id', $data['project_id'])->exists();
            if (! $ok) {
                return back()->withErrors(['project_id' => 'المشروع غير متاح.'])->withInput();
            }
        }

        $notes = trim((string) ($data['message'] ?? ''));
        if ($notes !== '') {
            $notes = "رسالة من الموقع:\n".$notes;
        }

        CrmLead::query()->create([
            'project_id' => $data['project_id'] ?? null,
            'created_by' => null,
            'assigned_to' => null,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'source' => 'website',
            'status' => 'new',
            'notes' => $notes !== '' ? $notes : null,
        ]);

        return redirect()
            ->route('site.contact')
            ->with('success', $site->contact_success ?: 'تم استلام طلبك بنجاح. سنتواصل معك قريباً.');
    }

    private function ensurePublished(object $site): void
    {
        if ($site->is_published ?? true) {
            return;
        }

        if (auth()->check()) {
            return;
        }

        abort(response()->view('site.offline', [
            'title' => ($site->brand ?? 'الموقع').' | قريباً',
            'site' => $site,
        ], 503));
    }
}
