<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShareholderRequest;
use App\Http\Requests\UpdateShareholderRequest;
use App\Models\Project;
use App\Models\Shareholder;
use App\Services\ShareholderAttributedFlowService;
use App\Services\ShareholderService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShareholderController extends Controller
{
    public function __construct(
        private readonly ShareholderService $shareholderService,
        private readonly ShareholderAttributedFlowService $attributedFlowService,
    ) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Shareholder::query();
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where('name', 'like', $like);
        }
        $filters->applyWhereDate($query, 'created_at');

        $propertyFinancials = $this->attributedFlowService->propertyFinancials($project);
        $propertyDevelopmentCosts = $this->attributedFlowService->propertyDevelopmentCosts($project);
        $shareholdersForKpis = (clone $query)->get();
        $attributedOperatingTotal = (float) $shareholdersForKpis->sum(
            fn ($sh) => $this->attributedFlowService->attributedOperatingFlow($sh, $project, $propertyFinancials)
        );
        $attributedCostTotal = (float) $shareholdersForKpis->sum(
            fn ($sh) => $this->attributedFlowService->attributedDevelopmentCostShare($sh, $project, $propertyDevelopmentCosts)
        );
        $currentAccountTotal = (float) $shareholdersForKpis->sum(
            function ($sh) use ($project, $propertyFinancials, $propertyDevelopmentCosts): float {
                $op = $this->attributedFlowService->attributedOperatingFlow($sh, $project, $propertyFinancials);
                $cost = $this->attributedFlowService->attributedDevelopmentCostShare($sh, $project, $propertyDevelopmentCosts);

                return $this->attributedFlowService->shareholderCurrentAccountApprox($op, $cost);
            }
        );

        $shareholderKpis = [
            'count' => (clone $query)->count(),
            'total_investment' => (float) (clone $query)->sum('total_investment'),
            'share_percentage' => (float) (clone $query)->sum('share_percentage'),
            'attributed_operating_total' => $attributedOperatingTotal,
            'attributed_cost_total' => $attributedCostTotal,
            'current_account_total' => $currentAccountTotal,
        ];

        $shareholders = $query->latest()->paginate(10)->withQueryString();
        $shareholders->getCollection()->transform(function (Shareholder $sh) use ($project, $propertyFinancials, $propertyDevelopmentCosts): Shareholder {
            $operating = $this->attributedFlowService->attributedOperatingFlow($sh, $project, $propertyFinancials);
            $cost = $this->attributedFlowService->attributedDevelopmentCostShare($sh, $project, $propertyDevelopmentCosts);
            $sh->setAttribute('attributed_operating_flow', $operating);
            $sh->setAttribute('attributed_development_cost_share', $cost);
            $sh->setAttribute(
                'shareholder_current_account',
                $this->attributedFlowService->shareholderCurrentAccountApprox($operating, $cost)
            );

            return $sh;
        });

        return view('shareholders.index', [
            'title' => 'المساهمين | Mohaseb Aqary',
            'pageTitle' => 'المساهمين',
            'project' => $project,
            'shareholderKpis' => $shareholderKpis,
            'shareholders' => $shareholders,
            'modules' => $this->modules(),
        ]);
    }

    public function create(Project $project): View
    {
        return view('shareholders.create', [
            'title' => 'إضافة مساهم | Mohaseb Aqary',
            'pageTitle' => 'إضافة مساهم',
            'project' => $project,
            'modules' => $this->modules(),
        ]);
    }

    public function store(Project $project, StoreShareholderRequest $request): RedirectResponse
    {
        $this->shareholderService->create($request->validated());

        return redirect()->route('shareholders.index', $project)->with('success', 'تم إضافة المساهم بنجاح.');
    }

    public function show(Project $project, Shareholder $shareholder): View
    {
        $shareholder = $this->shareholderService->findOrFail((int) $shareholder->id);
        $participations = $this->shareholderService->propertyParticipationsFor($shareholder);
        $propertyFinancials = $this->attributedFlowService->propertyFinancials($project);
        $attributedOperatingTotal = $this->attributedFlowService->attributedOperatingFlow(
            $shareholder,
            $project,
            $propertyFinancials
        );
        $attributedSaleVolumeShare = $this->attributedFlowService->attributedSaleVolumeShare(
            $shareholder,
            $project,
            $propertyFinancials
        );
        $propertyDevelopmentCosts = $this->attributedFlowService->propertyDevelopmentCosts($project);
        $attributedDevelopmentCostShare = $this->attributedFlowService->attributedDevelopmentCostShare(
            $shareholder,
            $project,
            $propertyDevelopmentCosts
        );
        $shareholderCurrentAccountApprox = $this->attributedFlowService->shareholderCurrentAccountApprox(
            $attributedOperatingTotal,
            $attributedDevelopmentCostShare
        );
        $participationFinancialBreakdown = $this->attributedFlowService->participationFinancialBreakdown(
            $participations,
            $propertyFinancials,
            $propertyDevelopmentCosts
        );

        return view('shareholders.show', [
            'title' => 'بروفايل المساهم | Mohaseb Aqary',
            'pageTitle' => 'بروفايل المساهم',
            'project' => $project,
            'shareholder' => $shareholder,
            'participations' => $participations,
            'propertyFinancials' => $propertyFinancials,
            'attributedOperatingTotal' => $attributedOperatingTotal,
            'attributedSaleVolumeShare' => $attributedSaleVolumeShare,
            'attributedDevelopmentCostShare' => $attributedDevelopmentCostShare,
            'shareholderCurrentAccountApprox' => $shareholderCurrentAccountApprox,
            'participationFinancialBreakdown' => $participationFinancialBreakdown,
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Project $project, Shareholder $shareholder): View
    {
        return view('shareholders.edit', [
            'title' => 'تعديل المساهم | Mohaseb Aqary',
            'pageTitle' => 'تعديل المساهم',
            'project' => $project,
            'shareholder' => $this->shareholderService->findOrFail((int) $shareholder->id),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateShareholderRequest $request, Project $project, Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->update($shareholder, $request->validated());

        return redirect()->route('shareholders.show', [$project, $shareholder])->with('success', 'تم تحديث المساهم بنجاح.');
    }

    public function destroy(Project $project, Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->delete($shareholder);

        return redirect()->route('shareholders.index', $project)->with('success', 'تم حذف المساهم بنجاح.');
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
