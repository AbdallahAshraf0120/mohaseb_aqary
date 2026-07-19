<?php

namespace App\Services;

use App\Models\LandParcel;
use App\Models\LandParcelPart;
use App\Models\LandParcelPayment;
use App\Models\LandParcelPaymentDistribution;
use App\Models\LandParcelShareholder;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class LandParcelPaymentService
{
    public function __construct(
        private readonly CashboxLedgerService $cashboxLedgerService,
        private readonly ShareholderLedgerService $shareholderLedgerService,
        private readonly OwnershipService $ownershipService,
    ) {}

    /**
     * @param  array{
     *     side: string,
     *     kind?: string,
     *     amount: float|int|string,
     *     paid_at: string,
     *     payment_method?: string,
     *     notes?: string|null,
     *     land_parcel_part_id?: int|null,
     *     paid_by_shareholder_id?: int|null
     * }  $data
     */
    public function create(LandParcel $parcel, array $data, ?User $user): LandParcelPayment
    {
        return DB::transaction(function () use ($parcel, $data, $user): LandParcelPayment {
            $isAdmin = $user instanceof User && $user->isAdmin();
            $side = (string) $data['side'];
            $amount = round((float) $data['amount'], 2);
            $partId = isset($data['land_parcel_part_id']) && $data['land_parcel_part_id'] !== null && $data['land_parcel_part_id'] !== ''
                ? (int) $data['land_parcel_part_id']
                : null;

            $paidById = isset($data['paid_by_shareholder_id']) && $data['paid_by_shareholder_id'] !== null && $data['paid_by_shareholder_id'] !== ''
                ? (int) $data['paid_by_shareholder_id']
                : null;

            if ($side === LandParcelPayment::SIDE_PURCHASE && $paidById !== null) {
                $member = LandParcelShareholder::query()
                    ->where('land_parcel_id', (int) $parcel->id)
                    ->where('shareholder_id', $paidById)
                    ->exists();
                if (! $member) {
                    throw new InvalidArgumentException('المساهم الدافع غير مرتبط بهذه الأرض.');
                }
            }

            $part = null;
            if ($partId !== null) {
                if ($side !== LandParcelPayment::SIDE_SALE) {
                    throw new InvalidArgumentException('أجزاء الأرض تُستخدم لتحصيل البيع فقط.');
                }
                $part = LandParcelPart::query()
                    ->where('land_parcel_id', (int) $parcel->id)
                    ->whereKey($partId)
                    ->first();
                if (! $part instanceof LandParcelPart) {
                    throw new InvalidArgumentException('الجزء غير موجود على هذه الأرض.');
                }
                $remaining = $part->remainingTotal();
            } else {
                $remaining = $parcel->remainingTotal($side);
            }

            if ($amount > $remaining + 0.01) {
                throw new InvalidArgumentException('المبلغ أكبر من المتبقي ('.$remaining.').');
            }

            $distributionStatus = 'none';
            if ($side === LandParcelPayment::SIDE_SALE) {
                $distributionStatus = $isAdmin ? 'pending' : 'pending';
            }

            $payload = [
                'land_parcel_id' => (int) $parcel->id,
                'land_parcel_part_id' => $partId,
                'side' => $side,
                'kind' => (string) ($data['kind'] ?? LandParcelPayment::KIND_OTHER),
                'amount' => $amount,
                'paid_at' => $data['paid_at'],
                'payment_method' => (string) ($data['payment_method'] ?? 'cash'),
                'notes' => $data['notes'] ?? null,
                'approval_status' => $isAdmin ? 'approved' : 'pending',
                'approved_at' => $isAdmin ? now() : null,
                'approved_by' => $isAdmin ? (int) $user->id : null,
                'created_by' => $user?->id,
            ];

            if (Schema::hasColumn('land_parcel_payments', 'paid_by_shareholder_id')) {
                $payload['paid_by_shareholder_id'] = $side === LandParcelPayment::SIDE_PURCHASE ? $paidById : null;
            }
            if (Schema::hasColumn('land_parcel_payments', 'distribution_status')) {
                $payload['distribution_status'] = $distributionStatus;
            }

            $payment = LandParcelPayment::query()->create($payload);

            $this->cashboxLedgerService->syncFromLandParcelPayment($payment);

            if ($part instanceof LandParcelPart) {
                $this->maybeMarkPartSold($part->fresh() ?? $part);
            }
            $this->maybeMarkParcelSold($parcel->fresh() ?? $parcel);

            if ($side === LandParcelPayment::SIDE_PURCHASE
                && $paidById !== null
                && ($payment->approval_status ?? '') === 'approved') {
                $this->ownershipService->syncLandActual((int) $parcel->id);
            }

            return $payment->fresh(['landParcel', 'part', 'creator', 'paidByShareholder']) ?? $payment;
        });
    }

    /**
     * توزيع تحصيل بيع معتمد على المساهمين حسب أساس مخطط أو فعلي (أو يدوي).
     *
     * @param  array<int, array{shareholder_id: int, amount: float|int|string}>|null  $manualRows
     */
    public function distributeSale(
        LandParcel $parcel,
        LandParcelPayment $payment,
        string $basis,
        ?User $user = null,
        ?array $manualRows = null
    ): void {
        if ($payment->side !== LandParcelPayment::SIDE_SALE) {
            throw new InvalidArgumentException('التوزيع متاح لتحصيلات البيع فقط.');
        }
        if (($payment->approval_status ?? '') !== 'approved') {
            throw new InvalidArgumentException('يجب اعتماد التحصيل قبل التوزيع.');
        }
        if (($payment->distribution_status ?? '') === 'distributed') {
            throw new InvalidArgumentException('تم توزيع هذا التحصيل مسبقًا.');
        }
        if (! in_array($basis, [
            LandParcelPaymentDistribution::BASIS_PLANNED,
            LandParcelPaymentDistribution::BASIS_ACTUAL,
            LandParcelPaymentDistribution::BASIS_MANUAL,
        ], true)) {
            throw new InvalidArgumentException('أساس التوزيع غير صالح.');
        }

        DB::transaction(function () use ($parcel, $payment, $basis, $user, $manualRows): void {
            $this->removeShareholderDistributions((int) $payment->id);

            $amount = round((float) $payment->amount, 2);
            $allocations = $basis === LandParcelPaymentDistribution::BASIS_MANUAL
                ? $this->manualAllocations($manualRows, $amount)
                : $this->percentageAllocations($parcel, $amount, $basis);

            $partLabel = $payment->part?->name;
            if ($partLabel === null && $payment->land_parcel_part_id) {
                $partLabel = LandParcelPart::query()->whereKey((int) $payment->land_parcel_part_id)->value('name');
            }

            foreach ($allocations as $row) {
                $shareholder = Shareholder::query()->find((int) $row['shareholder_id']);
                if (! $shareholder instanceof Shareholder) {
                    continue;
                }
                $shareAmount = round((float) $row['amount'], 2);
                if ($shareAmount <= 0) {
                    continue;
                }

                $note = sprintf(
                    'توزيع تحصيل بيع أرض «%s»%s — أساس %s%s — دفعة #%d',
                    $parcel->name,
                    $partLabel ? ' / جزء: '.$partLabel : '',
                    $basis === LandParcelPaymentDistribution::BASIS_PLANNED ? 'مخطط' : ($basis === LandParcelPaymentDistribution::BASIS_ACTUAL ? 'فعلي' : 'يدوي'),
                    isset($row['percentage']) ? sprintf(' — نسبة %.2f%%', $row['percentage']) : '',
                    (int) $payment->id
                );

                $payload = [
                    'land_parcel_id' => (int) $parcel->id,
                    'type' => ShareholderLedgerEntry::TYPE_ADJUSTMENT,
                    'direction' => ShareholderLedgerEntry::DIRECTION_CREDIT,
                    'amount' => $shareAmount,
                    'entry_date' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
                    'notes' => $note,
                    'skip_cashbox' => true,
                ];

                if (Schema::hasColumn('shareholder_ledger_entries', 'land_parcel_payment_id')) {
                    $payload['land_parcel_payment_id'] = (int) $payment->id;
                }

                $entry = $this->shareholderLedgerService->create($shareholder, $payload, $user);

                if (Schema::hasTable('land_parcel_payment_distributions')) {
                    LandParcelPaymentDistribution::query()->create([
                        'land_parcel_payment_id' => (int) $payment->id,
                        'shareholder_id' => (int) $shareholder->id,
                        'amount' => $shareAmount,
                        'basis' => $basis,
                        'percentage_used' => $row['percentage'] ?? null,
                        'ledger_entry_id' => (int) $entry->id,
                    ]);
                }
            }

            if (Schema::hasColumn('land_parcel_payments', 'distribution_status')) {
                $payment->update(['distribution_status' => 'distributed']);
            }
        });
    }

    /**
     * @return list<array{shareholder_id: int, amount: float, percentage?: float}>
     */
    private function percentageAllocations(LandParcel $parcel, float $amount, string $basis): array
    {
        $pctColumn = $basis === LandParcelPaymentDistribution::BASIS_ACTUAL
            ? 'actual_percentage'
            : 'planned_percentage';

        if (! Schema::hasColumn('land_parcel_shareholder', $pctColumn)) {
            $pctColumn = 'share_percentage';
        }

        $members = LandParcelShareholder::query()
            ->with('shareholder:id,name')
            ->where('land_parcel_id', (int) $parcel->id)
            ->where($pctColumn, '>', 0)
            ->orderBy('id')
            ->get()
            ->filter(fn (LandParcelShareholder $m) => $m->shareholder !== null)
            ->values();

        if ($members->isEmpty()) {
            throw new InvalidArgumentException('لا يوجد مساهمون بنسب صالحة للتوزيع.');
        }

        $totalPct = round((float) $members->sum($pctColumn), 4);
        if ($totalPct <= 0) {
            throw new InvalidArgumentException('مجموع النسب صفر — لا يمكن التوزيع.');
        }

        $allocated = 0.0;
        $lastIndex = $members->count() - 1;
        $rows = [];

        foreach ($members as $index => $membership) {
            $pct = (float) $membership->{$pctColumn};
            if ($index === $lastIndex) {
                $shareAmount = round($amount - $allocated, 2);
            } else {
                $shareAmount = round($amount * ($pct / $totalPct), 2);
                $allocated += $shareAmount;
            }
            $rows[] = [
                'shareholder_id' => (int) $membership->shareholder_id,
                'amount' => $shareAmount,
                'percentage' => $pct,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{shareholder_id: int, amount: float|int|string}>|null  $manualRows
     * @return list<array{shareholder_id: int, amount: float}>
     */
    private function manualAllocations(?array $manualRows, float $amount): array
    {
        if ($manualRows === null || $manualRows === []) {
            throw new InvalidArgumentException('أدخل توزيعًا يدويًا للمبالغ.');
        }

        $rows = [];
        $sum = 0.0;
        foreach ($manualRows as $row) {
            $shareAmount = round((float) ($row['amount'] ?? 0), 2);
            if ($shareAmount <= 0) {
                continue;
            }
            $rows[] = [
                'shareholder_id' => (int) $row['shareholder_id'],
                'amount' => $shareAmount,
            ];
            $sum += $shareAmount;
        }

        if (abs(round($sum, 2) - $amount) > 0.01) {
            throw new InvalidArgumentException('مجموع التوزيع اليدوي يجب أن يساوي مبلغ التحصيل ('.$amount.').');
        }

        return $rows;
    }

    public function delete(LandParcelPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $id = (int) $payment->id;
            $partId = $payment->land_parcel_part_id;
            $parcelId = (int) $payment->land_parcel_id;
            $wasPurchase = $payment->side === LandParcelPayment::SIDE_PURCHASE;
            $paidBy = $payment->paid_by_shareholder_id;

            $this->removeShareholderDistributions($id);

            $payment->delete();
            $this->cashboxLedgerService->removeLandParcelPayment($id);

            if ($partId && Schema::hasTable('land_parcel_parts')) {
                $part = LandParcelPart::query()->find($partId);
                if ($part instanceof LandParcelPart && $part->remainingTotal() > 0.01 && $part->status === 'sold') {
                    $part->update(['status' => 'reserved']);
                }
            }

            $parcel = LandParcel::query()->find($parcelId);
            if ($parcel instanceof LandParcel) {
                $this->maybeMarkParcelSold($parcel);
            }

            if ($wasPurchase && $paidBy) {
                $this->ownershipService->syncLandActual($parcelId);
            }
        });
    }

    private function removeShareholderDistributions(int $paymentId): void
    {
        if (Schema::hasTable('land_parcel_payment_distributions')) {
            $distQuery = LandParcelPaymentDistribution::query()->where('land_parcel_payment_id', $paymentId);
            $ledgerIds = $distQuery->pluck('ledger_entry_id')->filter()->all();
            $distQuery->delete();

            foreach ($ledgerIds as $ledgerId) {
                $entry = ShareholderLedgerEntry::withoutProjectScope()->find($ledgerId);
                if ($entry) {
                    $this->shareholderLedgerService->delete($entry);
                }
            }
        }

        if (! Schema::hasTable('shareholder_ledger_entries')) {
            return;
        }

        $query = ShareholderLedgerEntry::withoutProjectScope();

        if (Schema::hasColumn('shareholder_ledger_entries', 'land_parcel_payment_id')) {
            $query->where('land_parcel_payment_id', $paymentId);
        } else {
            $query->where('notes', 'like', '%دفعة #'.$paymentId.'%')
                ->where('type', ShareholderLedgerEntry::TYPE_ADJUSTMENT);
        }

        foreach ($query->get() as $entry) {
            $this->shareholderLedgerService->delete($entry);
        }
    }

    private function maybeMarkPartSold(LandParcelPart $part): void
    {
        if ((float) $part->sale_price <= 0) {
            return;
        }

        if ($part->remainingTotal() <= 0.01 && $part->status !== 'sold') {
            $part->update([
                'status' => 'sold',
                'sale_date' => $part->sale_date ?? now()->toDateString(),
            ]);
        } elseif ($part->approvedPaidTotal() > 0 && ! in_array($part->status, ['sold', 'cancelled'], true)) {
            $part->update(['status' => 'reserved']);
        }
    }

    private function maybeMarkParcelSold(LandParcel $parcel): void
    {
        if (! Schema::hasTable('land_parcel_parts')) {
            $salePrice = (float) ($parcel->sale_price ?? 0);
            if ($salePrice <= 0) {
                return;
            }
            if ($parcel->remainingTotal(LandParcelPayment::SIDE_SALE) <= 0.01 && $parcel->status !== 'sold') {
                $parcel->update(['status' => 'sold']);
            }

            return;
        }

        $partsCount = $parcel->parts()->count();
        if ($partsCount === 0) {
            $salePrice = (float) ($parcel->sale_price ?? 0);
            if ($salePrice > 0
                && $parcel->remainingTotal(LandParcelPayment::SIDE_SALE) <= 0.01
                && $parcel->status !== 'sold') {
                $parcel->update(['status' => 'sold']);
            }

            return;
        }

        $activeParts = $parcel->parts()->whereNotIn('status', ['cancelled'])->count();
        $soldParts = $parcel->parts()->where('status', 'sold')->count();
        if ($activeParts > 0 && $soldParts === $activeParts && $parcel->status !== 'sold') {
            $parcel->update(['status' => 'sold']);
        } elseif ($soldParts > 0 && $parcel->status === 'owned') {
            $parcel->update(['status' => 'for_sale']);
        }
    }
}
