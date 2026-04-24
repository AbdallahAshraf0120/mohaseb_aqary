<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Project;
use App\Services\ContractWordDocumentService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContractController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Contract::query()->with(['client:id,name', 'property:id,name', 'sale:id,down_payment']);
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->whereHas('client', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                    ->orWhereHas('property', fn ($p) => $p->where('name', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $forKpis = (clone $query)->get();
        $contractKpis = [
            'count' => $forKpis->count(),
            'net_value' => (float) $forKpis->sum(static fn ($c) => max(0, (float) $c->total_price - (float) ($c->sale?->down_payment ?? 0))),
            'remaining' => (float) $forKpis->sum(static fn ($c) => (float) $c->remaining_amount),
        ];

        return view('contracts.index', [
            'title' => 'العقود | Mohaseb Aqary',
            'pageTitle' => 'العقود',
            'project' => $project,
            'contractKpis' => $contractKpis,
            'contracts' => $query->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function show(Project $project, Contract $contract): View
    {
        return view('contracts.show', [
            'title' => 'تفاصيل العقد | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل العقد',
            'project' => $project,
            'contract' => $contract->load(['client', 'property', 'sale']),
            'hasContractTemplate' => $project->hasContractTemplate(),
            'modules' => $this->modules(),
        ]);
    }

    public function downloadWord(Project $project, Contract $contract, ContractWordDocumentService $documents): Response
    {
        if (! $project->hasContractTemplate()) {
            abort(404, 'لم يُرفَع قالب عقد ‎Word‎ لهذا المشروع. ارفع القالب من «تعديل المشروع».');
        }

        try {
            $path = $documents->buildFilledDocument($contract->loadMissing(['client', 'property', 'sale', 'project']));
        } catch (\Throwable) {
            abort(422, 'تعذّر إنشاء الملف من القالب. تأكد أن الملف ‎.docx‎ سليمًا.');
        }

        $year = $contract->created_at?->format('Y') ?? now()->format('Y');
        $ref = 'CT-'.$year.'-'.str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT);
        $filename = 'contract-'.$ref.'.docx';

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
            'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
            'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
            'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
            'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
