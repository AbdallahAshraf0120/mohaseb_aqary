<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * الأقساط المستحقة والمتأخرة: مبنية على جدول الأقساط المحسوب من خطة البيعة
 * (Sale::installmentScheduleWithPaymentSummary) مع توزيع التحصيلات المعتمدة على الأقدم أولًا.
 */
class InstallmentDueService
{
    /**
     * البيعات المعتمدة بالتقسيط في المشروع مع العقد والتحصيلات المعتمدة.
     *
     * @return Collection<int, Sale>
     */
    public function installmentSales(int $projectId, ?int $clientId = null): Collection
    {
        $q = Sale::query()
            ->withoutGlobalScope('project')
            ->where('project_id', $projectId)
            ->where('approval_status', 'approved')
            ->where('payment_type', 'installment')
            ->with([
                'client' => fn ($c) => $c->withoutGlobalScopes(),
                'property' => fn ($p) => $p->withoutGlobalScopes()->select('id', 'name'),
                'contract' => fn ($c) => $c->withoutGlobalScopes(),
                'contract.revenues' => fn ($r) => $r->withoutGlobalScopes()->where('approval_status', 'approved'),
            ]);
        if ($clientId !== null) {
            $q->where('client_id', $clientId);
        }

        return $q->get();
    }

    /**
     * كل بنود الجدول اللي لسه عليها رصيد ومستحقة لحد تاريخ معيّن.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function dueRows(int $projectId, Carbon $until, ?Carbon $from = null): Collection
    {
        $today = now()->startOfDay();
        $rows = collect();
        foreach ($this->installmentSales($projectId) as $sale) {
            foreach ($sale->installmentScheduleWithPaymentSummary() as $row) {
                if ($row['balance'] <= 0.00001) {
                    continue;
                }
                /** @var Carbon $due */
                $due = $row['due_date'];
                if ($due->gt($until) || ($from !== null && $due->lt($from))) {
                    continue;
                }
                $rows->push([
                    'sale' => $sale,
                    'client' => $sale->client,
                    'property' => $sale->property,
                    'number' => $row['number'],
                    'label' => $row['label'] ?? null,
                    'kind' => $row['kind'] ?? 'installment',
                    'due_date' => $due,
                    'amount' => (float) $row['amount'],
                    'paid' => (float) $row['paid'],
                    'balance' => (float) $row['balance'],
                    'days_late' => $due->lt($today) ? (int) $due->diffInDays($today) : 0,
                ]);
            }
        }

        return $rows->sortBy(fn (array $r) => $r['due_date']->timestamp)->values();
    }
}
