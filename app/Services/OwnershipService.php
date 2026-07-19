<?php

namespace App\Services;

use App\Models\LandParcel;
use App\Models\LandParcelPayment;
use App\Models\LandParcelShareholder;
use App\Models\OwnershipSnapshot;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class OwnershipService
{
    public function __construct(
        private readonly ShareholderLedgerService $ledgerService,
    ) {}

    public function percentageFor(float $investment, float $capital): float
    {
        $capital = round($capital, 2);
        if ($capital <= 0) {
            return 0.0;
        }

        return round(($investment / $capital) * 100, 2);
    }

    public function attributedPurchaseTotal(int $shareholderId, int $landParcelId): float
    {
        if (! Schema::hasTable('land_parcel_payments')
            || ! Schema::hasColumn('land_parcel_payments', 'paid_by_shareholder_id')) {
            return 0.0;
        }

        return round((float) LandParcelPayment::query()
            ->where('land_parcel_id', $landParcelId)
            ->where('side', LandParcelPayment::SIDE_PURCHASE)
            ->where('approval_status', 'approved')
            ->where('paid_by_shareholder_id', $shareholderId)
            ->sum('amount'), 2);
    }

    public function syncProjectActual(int $projectId): void
    {
        if (! Schema::hasColumn('project_shareholder', 'actual_investment')) {
            return;
        }

        $project = Project::query()->find($projectId);
        if (! $project instanceof Project) {
            return;
        }

        $members = ProjectShareholder::query()
            ->where('project_id', $projectId)
            ->get();

        $actualCapital = 0.0;
        foreach ($members as $membership) {
            $shareholder = Shareholder::query()->find((int) $membership->shareholder_id);
            $actual = $shareholder
                ? $shareholder->capitalDepositsTotal($projectId, null)
                : 0.0;
            $actualCapital += $actual;
            $membership->actual_investment = $actual;
        }

        $actualCapital = round($actualCapital, 2);
        foreach ($members as $membership) {
            $membership->actual_percentage = $this->percentageFor(
                (float) $membership->actual_investment,
                $actualCapital
            );
            $membership->save();
        }

        $project->actual_capital = $actualCapital;
        $project->saveQuietly();
    }

    public function syncLandActual(int $landParcelId): void
    {
        if (! Schema::hasColumn('land_parcel_shareholder', 'actual_investment')) {
            return;
        }

        $parcel = LandParcel::query()->find($landParcelId);
        if (! $parcel instanceof LandParcel) {
            return;
        }

        $members = LandParcelShareholder::query()
            ->where('land_parcel_id', $landParcelId)
            ->get();

        $actualCapital = 0.0;
        foreach ($members as $membership) {
            $shareholder = Shareholder::query()->find((int) $membership->shareholder_id);
            $capital = $shareholder
                ? $shareholder->capitalDepositsTotal(null, $landParcelId)
                : 0.0;
            $purchases = $this->attributedPurchaseTotal((int) $membership->shareholder_id, $landParcelId);
            $actual = round($capital + $purchases, 2);
            $actualCapital += $actual;
            $membership->actual_investment = $actual;
        }

        $actualCapital = round($actualCapital, 2);
        foreach ($members as $membership) {
            $membership->actual_percentage = $this->percentageFor(
                (float) $membership->actual_investment,
                $actualCapital
            );
            $membership->save();
        }

        $parcel->actual_capital = $actualCapital;
        $parcel->saveQuietly();
    }

    public function syncProjectPlannedPercentages(Project $project): void
    {
        if (! Schema::hasColumn('project_shareholder', 'planned_investment')) {
            return;
        }

        $plannedCapital = round((float) ($project->planned_capital ?? $project->capital ?? 0), 2);
        if (Schema::hasColumn('projects', 'planned_capital') && abs($plannedCapital - (float) $project->capital) > 0.001) {
            // keep capital alias in sync with planned
        }
        $project->planned_capital = $plannedCapital;
        if (Schema::hasColumn('projects', 'capital')) {
            $project->capital = $plannedCapital;
        }
        $project->saveQuietly();

        ProjectShareholder::query()
            ->where('project_id', (int) $project->id)
            ->get()
            ->each(function (ProjectShareholder $m) use ($plannedCapital): void {
                $inv = round((float) ($m->planned_investment ?? $m->total_investment ?? 0), 2);
                $pct = $this->percentageFor($inv, $plannedCapital);
                $m->planned_investment = $inv;
                $m->planned_percentage = $pct;
                $m->total_investment = $inv;
                $m->share_percentage = $pct;
                $m->save();
            });
    }

    public function syncLandPlannedPercentages(LandParcel $parcel): void
    {
        if (! Schema::hasColumn('land_parcel_shareholder', 'planned_investment')) {
            return;
        }

        $plannedCapital = round((float) ($parcel->planned_capital ?? $parcel->purchase_price ?? 0), 2);
        $parcel->planned_capital = $plannedCapital;
        if (Schema::hasColumn('land_parcels', 'purchase_price')) {
            $parcel->purchase_price = $plannedCapital;
        }
        $parcel->saveQuietly();

        LandParcelShareholder::query()
            ->where('land_parcel_id', (int) $parcel->id)
            ->get()
            ->each(function (LandParcelShareholder $m) use ($plannedCapital): void {
                $inv = round((float) ($m->planned_investment ?? $m->total_investment ?? 0), 2);
                $pct = $this->percentageFor($inv, $plannedCapital);
                $m->planned_investment = $inv;
                $m->planned_percentage = $pct;
                $m->total_investment = $inv;
                $m->share_percentage = $pct;
                $m->save();
            });
    }

    public function setPlannedInvestmentForProject(Shareholder $shareholder, Project $project, float $investment): ProjectShareholder
    {
        $investment = round($investment, 2);
        $plannedCapital = round((float) ($project->planned_capital ?? $project->capital ?? 0), 2);
        $pct = $this->percentageFor($investment, $plannedCapital);

        $membership = ProjectShareholder::query()->updateOrCreate(
            [
                'shareholder_id' => (int) $shareholder->id,
                'project_id' => (int) $project->id,
            ],
            [
                'planned_investment' => $investment,
                'planned_percentage' => $pct,
                'total_investment' => $investment,
                'share_percentage' => $pct,
            ]
        );

        $this->syncProjectActual((int) $project->id);

        return $membership->fresh() ?? $membership;
    }

    public function setPlannedInvestmentForLand(Shareholder $shareholder, LandParcel $parcel, float $investment): LandParcelShareholder
    {
        $investment = round($investment, 2);
        $plannedCapital = round((float) ($parcel->planned_capital ?? $parcel->purchase_price ?? 0), 2);
        $pct = $this->percentageFor($investment, $plannedCapital);

        $membership = LandParcelShareholder::query()->updateOrCreate(
            [
                'shareholder_id' => (int) $shareholder->id,
                'land_parcel_id' => (int) $parcel->id,
            ],
            [
                'planned_investment' => $investment,
                'planned_percentage' => $pct,
                'total_investment' => $investment,
                'share_percentage' => $pct,
            ]
        );

        $this->syncLandActual((int) $parcel->id);

        return $membership->fresh() ?? $membership;
    }

    /**
     * اعتماد المخطط كفعلي: نسخ النسب/التمويل المخطط إلى الفعلي مع تسوية الجاري عند الحاجة.
     */
    public function adoptPlanAsActual(string $targetType, int $targetId, ?User $user = null): OwnershipSnapshot
    {
        if (! in_array($targetType, [OwnershipSnapshot::TARGET_PROJECT, OwnershipSnapshot::TARGET_LAND_PARCEL], true)) {
            throw new InvalidArgumentException('نوع الهدف غير صالح.');
        }

        return DB::transaction(function () use ($targetType, $targetId, $user): OwnershipSnapshot {
            if ($targetType === OwnershipSnapshot::TARGET_PROJECT) {
                return $this->adoptProjectPlan($targetId, $user);
            }

            return $this->adoptLandPlan($targetId, $user);
        });
    }

    private function adoptProjectPlan(int $projectId, ?User $user): OwnershipSnapshot
    {
        $project = Project::query()->findOrFail($projectId);
        $members = ProjectShareholder::query()
            ->with('shareholder:id,name')
            ->where('project_id', $projectId)
            ->get();

        $before = $this->projectSnapshotPayload($project, $members);

        foreach ($members as $membership) {
            $shareholder = $membership->shareholder;
            if (! $shareholder instanceof Shareholder) {
                continue;
            }

            $plannedInv = round((float) ($membership->planned_investment ?? $membership->total_investment ?? 0), 2);
            if ($plannedInv >= 0.01) {
                $this->ledgerService->setFundingAmount(
                    $shareholder,
                    $projectId,
                    null,
                    $plannedInv,
                    $user,
                    true,
                    'اعتماد المخطط كفعلي — مطابقة رأس المال'
                );
            }

            $pct = round((float) ($membership->planned_percentage ?? $membership->share_percentage ?? 0), 2);
            $membership->refresh();
            $membership->actual_investment = $plannedInv;
            $membership->actual_percentage = $pct;
            $membership->save();
        }

        $project->actual_capital = round((float) ($project->planned_capital ?? $project->capital ?? 0), 2);
        $project->saveQuietly();

        $members = ProjectShareholder::query()
            ->with('shareholder:id,name')
            ->where('project_id', $projectId)
            ->get();

        return OwnershipSnapshot::query()->create([
            'target_type' => OwnershipSnapshot::TARGET_PROJECT,
            'target_id' => $projectId,
            'kind' => OwnershipSnapshot::KIND_ADOPT_PLAN,
            'before' => $before,
            'after' => $this->projectSnapshotPayload($project->fresh() ?? $project, $members),
            'created_by' => $user?->id,
        ]);
    }

    private function adoptLandPlan(int $landParcelId, ?User $user): OwnershipSnapshot
    {
        $parcel = LandParcel::query()->findOrFail($landParcelId);
        $members = LandParcelShareholder::query()
            ->with('shareholder:id,name')
            ->where('land_parcel_id', $landParcelId)
            ->get();

        $before = $this->landSnapshotPayload($parcel, $members);

        foreach ($members as $membership) {
            $shareholder = $membership->shareholder;
            if (! $shareholder instanceof Shareholder) {
                continue;
            }

            $plannedInv = round((float) ($membership->planned_investment ?? $membership->total_investment ?? 0), 2);
            $capitalOnly = $shareholder->capitalDepositsTotal(null, $landParcelId);
            $purchaseAttributed = $this->attributedPurchaseTotal((int) $shareholder->id, $landParcelId);
            // Align capital deposits so capital + purchases ≈ planned (purchases stay attributed separately)
            $targetCapital = max(0.01, round($plannedInv - $purchaseAttributed, 2));
            if ($plannedInv >= 0.01) {
                if ($targetCapital >= 0.01) {
                    $this->ledgerService->setFundingAmount(
                        $shareholder,
                        null,
                        $landParcelId,
                        $targetCapital,
                        $user,
                        true,
                        'اعتماد المخطط كفعلي — مطابقة رأس المال'
                    );
                } elseif ($capitalOnly >= 0.01 && $purchaseAttributed >= $plannedInv) {
                    // actual from purchases already covers plan; leave capital as-is
                }
            }

            $pct = round((float) ($membership->planned_percentage ?? $membership->share_percentage ?? 0), 2);
            $membership->actual_investment = $plannedInv;
            $membership->actual_percentage = $pct;
            $membership->save();
        }

        $parcel->actual_capital = round((float) ($parcel->planned_capital ?? $parcel->purchase_price ?? 0), 2);
        $parcel->saveQuietly();

        $members = LandParcelShareholder::query()
            ->with('shareholder:id,name')
            ->where('land_parcel_id', $landParcelId)
            ->get();

        return OwnershipSnapshot::query()->create([
            'target_type' => OwnershipSnapshot::TARGET_LAND_PARCEL,
            'target_id' => $landParcelId,
            'kind' => OwnershipSnapshot::KIND_ADOPT_PLAN,
            'before' => $before,
            'after' => $this->landSnapshotPayload($parcel->fresh() ?? $parcel, $members),
            'created_by' => $user?->id,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ProjectShareholder>  $members
     * @return array<string, mixed>
     */
    private function projectSnapshotPayload(Project $project, $members): array
    {
        return [
            'planned_capital' => (float) ($project->planned_capital ?? $project->capital ?? 0),
            'actual_capital' => (float) ($project->actual_capital ?? 0),
            'members' => $members->map(fn (ProjectShareholder $m) => [
                'shareholder_id' => (int) $m->shareholder_id,
                'name' => $m->shareholder?->name,
                'planned_investment' => (float) ($m->planned_investment ?? $m->total_investment ?? 0),
                'planned_percentage' => (float) ($m->planned_percentage ?? $m->share_percentage ?? 0),
                'actual_investment' => (float) ($m->actual_investment ?? 0),
                'actual_percentage' => (float) ($m->actual_percentage ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, LandParcelShareholder>  $members
     * @return array<string, mixed>
     */
    private function landSnapshotPayload(LandParcel $parcel, $members): array
    {
        return [
            'planned_capital' => (float) ($parcel->planned_capital ?? $parcel->purchase_price ?? 0),
            'actual_capital' => (float) ($parcel->actual_capital ?? 0),
            'members' => $members->map(fn (LandParcelShareholder $m) => [
                'shareholder_id' => (int) $m->shareholder_id,
                'name' => $m->shareholder?->name,
                'planned_investment' => (float) ($m->planned_investment ?? $m->total_investment ?? 0),
                'planned_percentage' => (float) ($m->planned_percentage ?? $m->share_percentage ?? 0),
                'actual_investment' => (float) ($m->actual_investment ?? 0),
                'actual_percentage' => (float) ($m->actual_percentage ?? 0),
            ])->values()->all(),
        ];
    }
}
