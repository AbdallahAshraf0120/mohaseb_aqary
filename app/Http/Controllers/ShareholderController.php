<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShareholderRequest;
use App\Http\Requests\UpdateShareholderRequest;
use App\Models\Project;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Services\ShareholderAttributedFlowService;
use App\Services\ShareholderLedgerService;
use App\Services\ShareholderService;
use App\Support\CurrentProject;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ShareholderController extends Controller
{
    public function __construct(
        private readonly ShareholderService $shareholderService,
        private readonly ShareholderAttributedFlowService $attributedFlowService,
        private readonly ShareholderLedgerService $ledgerService,
    ) {}

    public function index(Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $projectId = $request->filled('project_id') ? (int) $request->query('project_id') : null;

        $query = Shareholder::withoutProjectScope()->with('project:id,name');
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where('name', 'like', $like);
        }
        $filters->applyWhereDate($query, 'created_at');

        $shareholdersForKpis = (clone $query)->withSum([
            'ledgerEntries as ledger_credit_sum' => fn ($q) => $q->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT),
            'ledgerEntries as ledger_debit_sum' => fn ($q) => $q->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT),
            'ledgerEntries as capital_deposits_sum' => fn ($q) => $q->where('type', ShareholderLedgerEntry::TYPE_CAPITAL),
        ], 'amount')->get();

        [$financialsByProject, $costsByProject, $projectsById] = $this->projectAttributedCaches($shareholdersForKpis);

        $attributedOperatingTotal = 0.0;
        $attributedCostTotal = 0.0;
        $approxCurrentAccountTotal = 0.0;
        foreach ($shareholdersForKpis as $sh) {
            $pid = (int) $sh->project_id;
            $project = $projectsById->get($pid);
            if (! $project instanceof Project) {
                continue;
            }
            $financials = $financialsByProject[$pid] ?? [];
            $costs = $costsByProject[$pid] ?? [];
            $op = $this->attributedFlowService->attributedOperatingFlow($sh, $project, $financials);
            $cost = $this->attributedFlowService->attributedDevelopmentCostShare($sh, $project, $costs);
            $attributedOperatingTotal += $op;
            $attributedCostTotal += $cost;
            $approxCurrentAccountTotal += $this->attributedFlowService->shareholderCurrentAccountApprox($op, $cost);
        }

        $ledgerBalanceTotal = (float) $shareholdersForKpis->sum(
            fn ($sh) => round((float) ($sh->ledger_credit_sum ?? 0) - (float) ($sh->ledger_debit_sum ?? 0), 2)
        );

        $shareholderKpis = [
            'count' => (clone $query)->count(),
            'total_investment' => (float) $shareholdersForKpis->sum(fn ($sh) => (float) ($sh->capital_deposits_sum ?? 0)),
            'share_percentage' => (float) (clone $query)->sum('share_percentage'),
            'attributed_operating_total' => round($attributedOperatingTotal, 2),
            'attributed_cost_total' => round($attributedCostTotal, 2),
            'ledger_balance_total' => $ledgerBalanceTotal,
            'approx_current_account_total' => round($approxCurrentAccountTotal, 2),
        ];

        $shareholders = $query
            ->withSum([
                'ledgerEntries as ledger_credit_sum' => fn ($q) => $q->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT),
                'ledgerEntries as ledger_debit_sum' => fn ($q) => $q->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT),
                'ledgerEntries as capital_deposits_sum' => fn ($q) => $q->where('type', ShareholderLedgerEntry::TYPE_CAPITAL),
            ], 'amount')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $shareholders->getCollection()->transform(function (Shareholder $sh) use ($financialsByProject, $costsByProject, $projectsById): Shareholder {
            $pid = (int) $sh->project_id;
            $project = $projectsById->get($pid);
            $operating = 0.0;
            $cost = 0.0;
            if ($project instanceof Project) {
                $operating = $this->attributedFlowService->attributedOperatingFlow($sh, $project, $financialsByProject[$pid] ?? []);
                $cost = $this->attributedFlowService->attributedDevelopmentCostShare($sh, $project, $costsByProject[$pid] ?? []);
            }
            $ledgerBalance = round((float) ($sh->ledger_credit_sum ?? 0) - (float) ($sh->ledger_debit_sum ?? 0), 2);
            $sh->setAttribute('attributed_operating_flow', $operating);
            $sh->setAttribute('attributed_development_cost_share', $cost);
            $sh->setAttribute(
                'shareholder_current_account_approx',
                $this->attributedFlowService->shareholderCurrentAccountApprox($operating, $cost)
            );
            $sh->setAttribute('ledger_balance', $ledgerBalance);
            $sh->setAttribute('capital_deposits_total', round((float) ($sh->capital_deposits_sum ?? 0), 2));

            return $sh;
        });

        return view('shareholders.index', [
            'title' => 'إدارة المساهمين | Mohaseb Aqary',
            'pageTitle' => 'إدارة المساهمين',
            'shareholderKpis' => $shareholderKpis,
            'shareholders' => $shareholders,
            'projects' => Project::query()->listed()->orderBy('name')->get(['id', 'name']),
            'selectedProjectId' => $projectId,
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('shareholders.create', [
            'title' => 'إضافة مساهم | Mohaseb Aqary',
            'pageTitle' => 'إضافة مساهم',
            'shareholder' => new Shareholder,
            'projects' => Project::query()->listed()->orderBy('name')->get(['id', 'name']),
            'modules' => $this->modules(),
        ]);
    }

    public function store(StoreShareholderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        app(CurrentProject::class)->force((int) $data['project_id']);

        $shareholder = $this->shareholderService->create($data);

        $initialCapital = round((float) ($data['total_investment'] ?? 0), 2);
        if ($initialCapital > 0) {
            $this->ledgerService->create($shareholder, [
                'type' => ShareholderLedgerEntry::TYPE_CAPITAL,
                'amount' => $initialCapital,
                'entry_date' => now()->toDateString(),
                'notes' => 'إيداع رأس مال عند تسجيل المساهم',
            ], $request->user());
        }

        return redirect()->route('shareholders.index')->with('success', 'تم إضافة المساهم بنجاح.');
    }

    public function show(Shareholder $shareholder): View
    {
        $shareholder = $this->shareholderService->findOrFail((int) $shareholder->id);
        $project = Project::query()->findOrFail((int) $shareholder->project_id);
        app(CurrentProject::class)->force((int) $project->id);

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
        $ledgerEntries = $shareholder->ledgerEntries()
            ->with(['creator:id,name', 'treasuryTransaction:id,approval_status,type'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();
        $ledgerBalance = $shareholder->ledgerBalance();
        $capitalDepositsTotal = $shareholder->capitalDepositsTotal();

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
            'ledgerEntries' => $ledgerEntries,
            'ledgerBalance' => $ledgerBalance,
            'capitalDepositsTotal' => $capitalDepositsTotal,
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Shareholder $shareholder): View
    {
        $shareholder = $this->shareholderService->findOrFail((int) $shareholder->id);
        $shareholder->load('project:id,name');

        return view('shareholders.edit', [
            'title' => 'تعديل المساهم | Mohaseb Aqary',
            'pageTitle' => 'تعديل المساهم',
            'shareholder' => $shareholder,
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateShareholderRequest $request, Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->update($shareholder, $request->validated());

        return redirect()->route('shareholders.show', $shareholder)->with('success', 'تم تحديث المساهم بنجاح.');
    }

    public function destroy(Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->delete($shareholder);

        return redirect()->route('shareholders.index')->with('success', 'تم حذف المساهم بنجاح.');
    }

    /**
     * @param  Collection<int, Shareholder>  $shareholders
     * @return array{0: array<int, array>, 1: array<int, array>, 2: Collection<int, Project>}
     */
    private function projectAttributedCaches(Collection $shareholders): array
    {
        $projectIds = $shareholders->pluck('project_id')->unique()->filter()->map(fn ($id) => (int) $id)->values();
        $projectsById = Project::query()->whereIn('id', $projectIds)->get()->keyBy('id');

        $financialsByProject = [];
        $costsByProject = [];
        foreach ($projectsById as $pid => $project) {
            $financialsByProject[(int) $pid] = $this->attributedFlowService->propertyFinancials($project);
            $costsByProject[(int) $pid] = $this->attributedFlowService->propertyDevelopmentCosts($project);
        }

        return [$financialsByProject, $costsByProject, $projectsById];
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
        ];
    }
}
