<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachShareholderLandRequest;
use App\Http\Requests\AttachShareholderProjectRequest;
use App\Http\Requests\StoreShareholderRequest;
use App\Http\Requests\UpdateShareholderFundingRequest;
use App\Http\Requests\UpdateShareholderRequest;
use App\Models\LandParcel;
use App\Models\LandParcelPayment;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use InvalidArgumentException;
use App\Services\ShareholderAttributedFlowService;
use App\Services\ShareholderLedgerService;
use App\Services\ShareholderService;
use App\Support\CurrentProject;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ShareholderController extends Controller
{
    public function __construct(
        private readonly ShareholderService $shareholderService,
        private readonly ShareholderAttributedFlowService $attributedFlowService,
        private readonly ShareholderLedgerService $ledgerService,
    ) {}

    public function index(Request $request): View
    {
        if (! Schema::hasTable('project_shareholder')) {
            abort(503, 'قاعدة البيانات غير محدّثة. شغّل على السيرفر: php artisan migrate --force');
        }

        $filters = ListingFilters::fromRequest($request);
        $projectId = $request->filled('project_id') ? (int) $request->query('project_id') : null;

        $query = Shareholder::query()->with(['projectMemberships.project:id,name']);
        if ($projectId) {
            $query->whereHas('projectMemberships', fn ($q) => $q->where('project_id', $projectId));
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

        $shareholderKpis = [
            'count' => (clone $query)->count(),
            'total_investment' => (float) $shareholdersForKpis->sum(fn ($sh) => (float) ($sh->capital_deposits_sum ?? 0)),
            'ledger_balance_total' => (float) $shareholdersForKpis->sum(
                fn ($sh) => round((float) ($sh->ledger_credit_sum ?? 0) - (float) ($sh->ledger_debit_sum ?? 0), 2)
            ),
            'memberships_count' => (float) ProjectShareholder::query()
                ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
                ->count(),
        ];

        $shareholders = $query
            ->withSum([
                'ledgerEntries as ledger_credit_sum' => fn ($q) => $q->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT),
                'ledgerEntries as ledger_debit_sum' => fn ($q) => $q->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT),
                'ledgerEntries as capital_deposits_sum' => fn ($q) => $q->where('type', ShareholderLedgerEntry::TYPE_CAPITAL),
            ], 'amount')
            ->withCount('projectMemberships')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $shareholders->getCollection()->transform(function (Shareholder $sh): Shareholder {
            $sh->setAttribute(
                'ledger_balance',
                round((float) ($sh->ledger_credit_sum ?? 0) - (float) ($sh->ledger_debit_sum ?? 0), 2)
            );
            $sh->setAttribute('capital_deposits_total', round((float) ($sh->capital_deposits_sum ?? 0), 2));

            return $sh;
        });

        return view('shareholders.index', [
            'title' => 'إدارة المساهمين | Mohaseb Aqary',
            'pageTitle' => 'إدارة المساهمين',
            'shareholderKpis' => $shareholderKpis,
            'shareholders' => $shareholders,
            'projects' => Project::query()->listed()->orderBy('name')->get(['id', 'name', 'capital']),
            'selectedProjectId' => $projectId,
        ]);
    }

    public function create(): View
    {
        $landsReady = Schema::hasTable('land_parcels') && Schema::hasTable('land_parcel_shareholder');
        $landColumns = ['id', 'name', 'purchase_price'];
        if ($landsReady && Schema::hasColumn('land_parcels', 'planned_capital')) {
            $landColumns[] = 'planned_capital';
        }

        $projectColumns = ['id', 'name', 'capital'];
        if (Schema::hasColumn('projects', 'planned_capital')) {
            $projectColumns[] = 'planned_capital';
        }

        return view('shareholders.create', [
            'title' => 'إضافة مساهم | Mohaseb Aqary',
            'pageTitle' => 'إضافة مساهم',
            'shareholder' => new Shareholder,
            'projects' => Project::query()->listed()->orderBy('name')->get($projectColumns),
            'lands' => $landsReady
                ? LandParcel::query()
                    ->where(function ($q): void {
                        $q->where('purchase_price', '>', 0);
                        if (Schema::hasColumn('land_parcels', 'planned_capital')) {
                            $q->orWhere('planned_capital', '>', 0);
                        }
                    })
                    ->orderBy('name')
                    ->get($landColumns)
                : collect(),
            'landsReady' => $landsReady,
        ]);
    }

    public function store(StoreShareholderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $linkType = (string) $data['link_type'];
        $percentage = round((float) $data['share_percentage'], 2);
        $funding = isset($data['total_investment']) && $data['total_investment'] !== null && $data['total_investment'] !== ''
            ? round((float) $data['total_investment'], 2)
            : 0.0;

        $shareholder = $this->shareholderService->create($data);

        if ($linkType === 'land') {
            $parcel = LandParcel::query()->findOrFail((int) $data['land_parcel_id']);
            $this->shareholderService->attachToLandParcel($shareholder, $parcel, $percentage);

            app(CurrentProject::class)->force(null);
            if ($funding >= 0.01) {
                $this->ledgerService->create($shareholder, [
                    'project_id' => null,
                    'land_parcel_id' => (int) $parcel->id,
                    'type' => ShareholderLedgerEntry::TYPE_CAPITAL,
                    'amount' => $funding,
                    'entry_date' => now()->toDateString(),
                    'notes' => 'إيداع رأس مال عند تسجيل المساهم على الأرض',
                ], $request->user());
            }

            return redirect()
                ->route('shareholders.show', $shareholder)
                ->with('success', 'تم إضافة المساهم وربطه بالأرض بالنسبة المخططة.');
        }

        $project = Project::query()->findOrFail((int) $data['project_id']);
        $this->shareholderService->attachToProject($shareholder, $project, $percentage);

        app(CurrentProject::class)->force((int) $project->id);
        if ($funding >= 0.01) {
            $this->ledgerService->create($shareholder, [
                'project_id' => (int) $project->id,
                'type' => ShareholderLedgerEntry::TYPE_CAPITAL,
                'amount' => $funding,
                'entry_date' => now()->toDateString(),
                'notes' => 'إيداع رأس مال عند تسجيل المساهم في المشروع',
            ], $request->user());
        }

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', 'تم إضافة المساهم وربطه بالمشروع بالنسبة المخططة.');
    }

    public function show(Shareholder $shareholder): View
    {
        if (! Schema::hasTable('project_shareholder')) {
            abort(503, 'قاعدة البيانات غير محدّثة. شغّل على السيرفر: php artisan migrate --force');
        }

        $shareholder = $this->shareholderService->findOrFail((int) $shareholder->id);
        $memberships = $shareholder->projectMemberships()->with('project:id,name,capital')->orderBy('project_id')->get();

        $landsReady = Schema::hasTable('land_parcel_shareholder')
            && Schema::hasColumn('shareholder_ledger_entries', 'land_parcel_id');

        $landMemberships = $landsReady
            ? $shareholder->landMemberships()->with('landParcel:id,name,purchase_price,status')->orderBy('land_parcel_id')->get()
            : collect();

        $participations = $this->shareholderService->propertyParticipationsFor($shareholder);
        $ledgerWith = [
            'creator:id,name',
            'treasuryTransaction:id,approval_status,type',
            'project:id,name',
        ];
        if ($landsReady) {
            $ledgerWith[] = 'landParcel:id,name';
        }
        $ledgerEntries = $shareholder->ledgerEntries()
            ->with($ledgerWith)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $landPayments = collect();
        if (Schema::hasTable('land_parcel_payments')) {
            $sid = (int) $shareholder->id;
            $landPayments = LandParcelPayment::query()
                ->with(['landParcel:id,name', 'part:id,name', 'paidByShareholder:id,name', 'receivedByShareholder:id,name'])
                ->where(function ($q) use ($sid): void {
                    $q->where('paid_by_shareholder_id', $sid);
                    if (Schema::hasColumn('land_parcel_payments', 'received_by_shareholder_id')) {
                        $q->orWhere('received_by_shareholder_id', $sid);
                    }
                })
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        $projectBreakdown = [];
        foreach ($memberships as $membership) {
            $project = $membership->project;
            if (! $project instanceof Project) {
                continue;
            }
            $propertyFinancials = $this->attributedFlowService->propertyFinancials($project);
            $propertyDevelopmentCosts = $this->attributedFlowService->propertyDevelopmentCosts($project);
            $op = $this->attributedFlowService->attributedOperatingFlow($shareholder, $project, $propertyFinancials);
            $cost = $this->attributedFlowService->attributedDevelopmentCostShare($shareholder, $project, $propertyDevelopmentCosts);
            $projectBreakdown[] = (object) [
                'membership' => $membership,
                'project' => $project,
                'ledger_balance' => $shareholder->ledgerBalance((int) $project->id),
                'capital_deposits' => $shareholder->capitalDepositsTotal((int) $project->id),
                'attributed_operating' => $op,
                'attributed_cost' => $cost,
                'approx_current' => $this->attributedFlowService->shareholderCurrentAccountApprox($op, $cost),
            ];
        }

        $landBreakdown = $landMemberships->map(function ($membership) use ($shareholder) {
            $parcel = $membership->landParcel;

            return (object) [
                'membership' => $membership,
                'parcel' => $parcel,
                'ledger_balance' => $parcel
                    ? $shareholder->ledgerBalance(null, (int) $parcel->id)
                    : 0.0,
                'capital_deposits' => $parcel
                    ? $shareholder->capitalDepositsTotal(null, (int) $parcel->id)
                    : 0.0,
            ];
        });

        $attachedProjectIds = $memberships->pluck('project_id')->all();
        $availableProjects = Project::query()
            ->listed()
            ->whereNotIn('id', $attachedProjectIds)
            ->orderBy('name')
            ->get(['id', 'name', 'capital']);

        $attachedLandIds = $landMemberships->pluck('land_parcel_id')->all();
        $availableLands = $landsReady
            ? LandParcel::query()
                ->whereNotIn('id', $attachedLandIds)
                ->where('purchase_price', '>', 0)
                ->orderBy('name')
                ->get(['id', 'name', 'purchase_price', 'status'])
            : collect();

        return view('shareholders.show', [
            'title' => 'بروفايل المساهم | Mohaseb Aqary',
            'pageTitle' => 'بروفايل المساهم',
            'shareholder' => $shareholder,
            'memberships' => $memberships,
            'landMemberships' => $landMemberships,
            'projectBreakdown' => $projectBreakdown,
            'landBreakdown' => $landBreakdown,
            'participations' => $participations,
            'ledgerEntries' => $ledgerEntries,
            'landPayments' => $landPayments,
            'ledgerBalance' => $shareholder->ledgerBalance(),
            'capitalDepositsTotal' => $shareholder->capitalDepositsTotal(),
            'availableProjects' => $availableProjects,
            'availableLands' => $availableLands,
            'landsSchemaReady' => $landsReady,
        ]);
    }

    public function edit(Shareholder $shareholder): View
    {
        $shareholder = $this->shareholderService->findOrFail((int) $shareholder->id);

        return view('shareholders.edit', [
            'title' => 'تعديل المساهم | Mohaseb Aqary',
            'pageTitle' => 'تعديل المساهم',
            'shareholder' => $shareholder,
        ]);
    }

    public function update(UpdateShareholderRequest $request, Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->update($shareholder, $request->validated());

        return redirect()->route('shareholders.show', $shareholder)->with('success', 'تم تحديث بيانات المساهم بنجاح.');
    }

    public function destroy(Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->delete($shareholder);

        return redirect()->route('shareholders.index')->with('success', 'تم حذف المساهم بنجاح.');
    }

    public function attachProject(AttachShareholderProjectRequest $request, Shareholder $shareholder): RedirectResponse
    {
        $data = $request->validated();
        $project = Project::query()->findOrFail((int) $data['project_id']);
        $percentage = round((float) $data['share_percentage'], 2);
        $funding = isset($data['total_investment']) && $data['total_investment'] !== null && $data['total_investment'] !== ''
            ? round((float) $data['total_investment'], 2)
            : 0.0;

        $this->shareholderService->attachToProject($shareholder, $project, $percentage);
        app(CurrentProject::class)->force((int) $project->id);
        if ($funding >= 0.01) {
            $this->ledgerService->create($shareholder, [
                'project_id' => (int) $project->id,
                'type' => ShareholderLedgerEntry::TYPE_CAPITAL,
                'amount' => $funding,
                'entry_date' => now()->toDateString(),
                'notes' => 'إيداع رأس مال عند الربط بمشروع جديد',
            ], $request->user());
        }

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', $funding >= 0.01
                ? 'تم ربط المساهم بالمشروع وتسجيل رأس المال في الجاري.'
                : 'تم ربط المساهم بالمشروع بالنسبة المخططة.');
    }

    public function attachLand(AttachShareholderLandRequest $request, Shareholder $shareholder): RedirectResponse
    {
        $data = $request->validated();
        $parcel = LandParcel::query()->findOrFail((int) $data['land_parcel_id']);
        $percentage = round((float) $data['share_percentage'], 2);
        $funding = isset($data['total_investment']) && $data['total_investment'] !== null && $data['total_investment'] !== ''
            ? round((float) $data['total_investment'], 2)
            : 0.0;

        $this->shareholderService->attachToLandParcel($shareholder, $parcel, $percentage);
        app(CurrentProject::class)->force(null);
        if ($funding >= 0.01) {
            $this->ledgerService->create($shareholder, [
                'project_id' => null,
                'land_parcel_id' => (int) $parcel->id,
                'type' => ShareholderLedgerEntry::TYPE_CAPITAL,
                'amount' => $funding,
                'entry_date' => now()->toDateString(),
                'notes' => 'إيداع رأس مال عند الربط بأرض بيع/شراء',
            ], $request->user());
        }

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', $funding >= 0.01
                ? 'تم ربط المساهم بالأرض وتسجيل رأس المال في الجاري.'
                : 'تم ربط المساهم بالأرض بالنسبة المخططة.');
    }

    public function updateFunding(UpdateShareholderFundingRequest $request, Shareholder $shareholder): RedirectResponse
    {
        $data = $request->validated();
        $isProject = $data['target_type'] === 'project';
        $percentage = round((float) $data['share_percentage'], 2);
        $hasFunding = array_key_exists('total_investment', $data)
            && $data['total_investment'] !== null
            && $data['total_investment'] !== '';
        $funding = $hasFunding ? round((float) $data['total_investment'], 2) : null;

        try {
            if ($isProject) {
                $project = Project::query()->findOrFail((int) $data['target_id']);
                $this->shareholderService->attachToProject($shareholder, $project, $percentage);
                if ($funding !== null) {
                    $this->ledgerService->setFundingAmount(
                        $shareholder,
                        (int) $project->id,
                        null,
                        $funding,
                        $request->user()
                    );
                }
            } else {
                $parcel = LandParcel::query()->findOrFail((int) $data['target_id']);
                $this->shareholderService->attachToLandParcel($shareholder, $parcel, $percentage);
                if ($funding !== null) {
                    $this->ledgerService->setFundingAmount(
                        $shareholder,
                        null,
                        (int) $parcel->id,
                        $funding,
                        $request->user()
                    );
                }
            }
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('shareholders.show', $shareholder)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', 'تم تحديث النسبة المخططة'.($funding !== null ? ' والتمويل الفعلي' : '').'.');
    }
}
