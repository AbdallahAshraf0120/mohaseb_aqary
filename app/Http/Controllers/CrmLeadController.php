<?php

namespace App\Http\Controllers;

use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Support\CurrentProject;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmLeadController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $projectId = app(CurrentProject::class)->id();
        if (! $projectId) {
            return redirect()->route('projects.index')->with('error', 'اختر مشروع أولاً لفتح CRM.');
        }

        $filters = ListingFilters::fromRequest($request);
        $status = trim((string) $request->query('status', ''));

        $query = CrmLead::query()
            ->where('project_id', $projectId)
            ->with([
                'creator:id,name',
                'assignee:id,name',
            ]);

        if (! $request->user()?->can('crm.manage')) {
            $query->where('assigned_to', $request->user()?->id);
        }

        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $filters->applyWhereDate($query, 'created_at');

        $leadKpisQuery = (clone $query);
        $leadKpis = [
            'count' => (clone $leadKpisQuery)->count(),
            'new' => (clone $leadKpisQuery)->where('status', 'new')->count(),
            'follow_up' => (clone $leadKpisQuery)->where('status', 'follow_up')->count(),
            'interested' => (clone $leadKpisQuery)->where('status', 'interested')->count(),
            'won' => (clone $leadKpisQuery)->where('status', 'won')->count(),
            'lost' => (clone $leadKpisQuery)->where('status', 'lost')->count(),
            'due' => (clone $leadKpisQuery)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', now())
                ->count(),
        ];

        $leads = $query
            ->orderByRaw("case status when 'new' then 0 when 'follow_up' then 1 when 'interested' then 2 when 'won' then 3 else 4 end")
            ->orderByRaw('next_follow_up_at is null, next_follow_up_at asc')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('crm.leads.index', [
            'title' => 'CRM | Mohaseb Aqary',
            'pageTitle' => 'CRM - متابعة العملاء',
            'leadKpis' => $leadKpis,
            'leads' => $leads,
            'status' => $status,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $projectId = app(CurrentProject::class)->id();
        if (! $projectId) {
            return redirect()->route('projects.index')->with('error', 'اختر مشروع أولاً لإضافة عميل محتمل.');
        }

        return view('crm.leads.create', [
            'title' => 'إضافة عميل محتمل | Mohaseb Aqary',
            'pageTitle' => 'إضافة عميل محتمل',
            'lead' => new CrmLead(['status' => 'new']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $projectId = app(CurrentProject::class)->id();
        if (! $projectId) {
            return redirect()->route('projects.index')->with('error', 'اختر مشروع أولاً لإضافة عميل محتمل.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(['new', 'follow_up', 'interested', 'won', 'lost'])],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['project_id'] = $projectId;
        $data['created_by'] = $request->user()?->id;
        $data['assigned_to'] = $request->user()?->id;

        $lead = CrmLead::query()->create($data);

        return redirect()
            ->route('crm-leads.show', $lead)
            ->with('success', 'تم إضافة العميل المحتمل بنجاح.');
    }

    public function show(CrmLead $lead, Request $request): View
    {
        $this->ensureLeadInCurrentProject($lead);
        $this->ensureLeadAccessible($lead, $request);

        $lead->load([
            'creator:id,name',
            'assignee:id,name',
            'activities' => fn ($q) => $q->with(['user:id,name'])->latest('happened_at')->latest('id'),
        ]);

        return view('crm.leads.show', [
            'title' => 'تفاصيل عميل محتمل | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل عميل محتمل',
            'lead' => $lead,
        ]);
    }

    public function edit(CrmLead $lead, Request $request): View
    {
        $this->ensureLeadInCurrentProject($lead);
        $this->ensureLeadAccessible($lead, $request);

        return view('crm.leads.edit', [
            'title' => 'تعديل عميل محتمل | Mohaseb Aqary',
            'pageTitle' => 'تعديل عميل محتمل',
            'lead' => $lead,
        ]);
    }

    public function update(CrmLead $lead, Request $request): RedirectResponse
    {
        $this->ensureLeadInCurrentProject($lead);
        $this->ensureLeadAccessible($lead, $request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(['new', 'follow_up', 'interested', 'won', 'lost'])],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead->update($data);

        return redirect()
            ->route('crm-leads.show', $lead)
            ->with('success', 'تم تحديث العميل المحتمل بنجاح.');
    }

    public function destroy(CrmLead $lead, Request $request): RedirectResponse
    {
        $this->ensureLeadInCurrentProject($lead);
        $this->ensureLeadAccessible($lead, $request);

        $lead->delete();

        return redirect()
            ->route('crm-leads.index')
            ->with('success', 'تم حذف العميل المحتمل.');
    }

    public function storeActivity(CrmLead $lead, Request $request): RedirectResponse
    {
        $this->ensureLeadInCurrentProject($lead);
        $this->ensureLeadAccessible($lead, $request);

        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(['call', 'whatsapp', 'meeting', 'note'])],
            'note' => ['nullable', 'string', 'max:5000'],
            'happened_at' => ['nullable', 'date'],
        ]);

        $data['lead_id'] = $lead->id;
        $data['user_id'] = $request->user()?->id;
        $data['happened_at'] = $data['happened_at'] ?? now();

        CrmLeadActivity::query()->create($data);

        if ($lead->next_follow_up_at === null && in_array($lead->status, ['new', 'follow_up'], true)) {
            $lead->update(['status' => 'follow_up']);
        }

        return redirect()
            ->route('crm-leads.show', $lead)
            ->with('success', 'تم تسجيل المتابعة.');
    }

    private function ensureLeadInCurrentProject(CrmLead $lead): void
    {
        $projectId = app(CurrentProject::class)->id();
        if (! $projectId) {
            abort(403, 'لا يوجد مشروع محدد حاليًا.');
        }
        if ((int) $lead->project_id !== (int) $projectId) {
            abort(404);
        }
    }

    private function ensureLeadAccessible(CrmLead $lead, Request $request): void
    {
        if ($request->user()?->can('crm.manage')) {
            return;
        }

        if ((int) $lead->assigned_to !== (int) ($request->user()?->id ?? 0)) {
            abort(403, 'ليس لديك صلاحية للوصول لهذا العميل.');
        }
    }
}

