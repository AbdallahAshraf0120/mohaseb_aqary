<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Property;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Shareholder;
use Illuminate\Support\Collection;

/**
 * يوزّع على المساهمين ما يخص كل عقار من التحصيلات (عبر العقود) ومقدمات المبيعات،
 * مضروباً في نسبة المساهم المحفوظة في توزيع المساهمين على العقار.
 */
final class ShareholderAttributedFlowService
{
    /**
     * @return array<int, array{revenues: float, down_payments: float, sale_volume: float}>
     */
    public function propertyFinancials(Project $project): array
    {
        $projectId = (int) $project->id;

        $revenueByProperty = Revenue::query()
            ->join('contracts', 'contracts.id', '=', 'revenues.contract_id')
            ->where('contracts.project_id', $projectId)
            ->groupBy('contracts.property_id')
            ->selectRaw('contracts.property_id as property_id, COALESCE(SUM(revenues.amount), 0) as total')
            ->pluck('total', 'property_id');

        $salesAgg = Sale::query()
            ->where('project_id', $projectId)
            ->groupBy('property_id')
            ->selectRaw(
                'property_id, COALESCE(SUM(COALESCE(down_payment, 0)), 0) as down_total, COALESCE(SUM(sale_price), 0) as price_total'
            )
            ->get()
            ->keyBy('property_id');

        $ids = $revenueByProperty->keys()->merge($salesAgg->keys())->unique()->map(static fn ($k) => (int) $k)->values();

        $out = [];
        foreach ($ids as $propId) {
            $row = $salesAgg->get($propId);
            $out[$propId] = [
                'revenues' => (float) ($revenueByProperty[$propId] ?? 0),
                'down_payments' => $row ? (float) $row->down_total : 0.0,
                'sale_volume' => $row ? (float) $row->price_total : 0.0,
            ];
        }

        return $out;
    }

    public function allocationPercent(Property $property, Shareholder $shareholder): float
    {
        $id = (int) $shareholder->id;
        $name = (string) $shareholder->name;
        $rows = collect($property->shareholder_allocations ?? []);
        $match = $rows->first(function (array $row) use ($id, $name): bool {
            if (isset($row['shareholder_id']) && (int) $row['shareholder_id'] === $id) {
                return true;
            }
            $rowName = $row['shareholder_name'] ?? null;

            return $rowName !== null && $rowName === $name;
        });

        if ($match === null) {
            return 0.0;
        }

        return (float) ($match['percentage'] ?? 0);
    }

    /**
     * حصة المساهم من (تحصيلات العقار + مقدمات البيع على نفس العقار) حسب نسبته في التوزيع.
     *
     * @param  array<int, array{revenues: float, down_payments: float, sale_volume: float}>|null  $propertyFinancials
     */
    public function attributedOperatingFlow(Shareholder $shareholder, Project $project, ?array $propertyFinancials = null): float
    {
        $propertyFinancials ??= $this->propertyFinancials($project);

        $properties = Property::query()
            ->where('project_id', (int) $project->id)
            ->whereNotNull('shareholder_allocations')
            ->get(['id', 'name', 'shareholder_allocations']);

        $total = 0.0;
        foreach ($properties as $property) {
            $pct = $this->allocationPercent($property, $shareholder);
            if ($pct <= 0) {
                continue;
            }
            $pid = (int) $property->id;
            $f = $propertyFinancials[$pid] ?? [
                'revenues' => 0.0,
                'down_payments' => 0.0,
                'sale_volume' => 0.0,
            ];
            $pool = (float) $f['revenues'] + (float) $f['down_payments'];
            $total += $pool * ($pct / 100.0);
        }

        return round($total, 2);
    }

    /**
     * حصة المساهم من إجمالي سعر البيعات على العقار (كمبيالة / حجم صفقات).
     *
     * @param  array<int, array{revenues: float, down_payments: float, sale_volume: float}>|null  $propertyFinancials
     */
    public function attributedSaleVolumeShare(Shareholder $shareholder, Project $project, ?array $propertyFinancials = null): float
    {
        $propertyFinancials ??= $this->propertyFinancials($project);

        $properties = Property::query()
            ->where('project_id', (int) $project->id)
            ->whereNotNull('shareholder_allocations')
            ->get(['id', 'shareholder_allocations']);

        $total = 0.0;
        foreach ($properties as $property) {
            $pct = $this->allocationPercent($property, $shareholder);
            if ($pct <= 0) {
                continue;
            }
            $pid = (int) $property->id;
            $vol = (float) (($propertyFinancials[$pid] ?? [])['sale_volume'] ?? 0.0);
            $total += $vol * ($pct / 100.0);
        }

        return round($total, 2);
    }

    /**
     * @param  Collection<int, object{property: Property, percentage: float, allocation: array<string, mixed>}>  $participations
     * @param  array<int, array{revenues: float, down_payments: float, sale_volume: float}>  $propertyFinancials
     * @return list<array{property_id: int, revenues: float, down_payments: float, sale_volume: float, percentage: float, operating_pool: float, attributed_operating: float, attributed_sale_volume: float}>
     */
    public function participationFinancialBreakdown(Collection $participations, array $propertyFinancials): array
    {
        $rows = [];
        foreach ($participations as $item) {
            $pid = (int) $item->property->id;
            $f = $propertyFinancials[$pid] ?? [
                'revenues' => 0.0,
                'down_payments' => 0.0,
                'sale_volume' => 0.0,
            ];
            $pct = (float) $item->percentage;
            $pool = (float) $f['revenues'] + (float) $f['down_payments'];
            $rows[] = [
                'property_id' => $pid,
                'revenues' => round((float) $f['revenues'], 2),
                'down_payments' => round((float) $f['down_payments'], 2),
                'sale_volume' => round((float) $f['sale_volume'], 2),
                'percentage' => round($pct, 2),
                'operating_pool' => round($pool, 2),
                'attributed_operating' => round($pool * ($pct / 100.0), 2),
                'attributed_sale_volume' => round((float) $f['sale_volume'] * ($pct / 100.0), 2),
            ];
        }

        return $rows;
    }
}
