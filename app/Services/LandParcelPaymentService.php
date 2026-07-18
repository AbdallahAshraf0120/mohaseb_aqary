<?php

namespace App\Services;

use App\Models\LandParcel;
use App\Models\LandParcelPart;
use App\Models\LandParcelPayment;
use App\Models\LandParcelShareholder;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LandParcelPaymentService
{
    public function __construct(
        private readonly CashboxLedgerService $cashboxLedgerService,
        private readonly ShareholderLedgerService $shareholderLedgerService,
    ) {}

    /**
     * @param  array{
     *     side: string,
     *     kind?: string,
     *     amount: float|int|string,
     *     paid_at: string,
     *     payment_method?: string,
     *     notes?: string|null,
     *     land_parcel_part_id?: int|null
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

            $part = null;
            if ($partId !== null) {
                if ($side !== LandParcelPayment::SIDE_SALE) {
                    throw new \InvalidArgumentException('أجزاء الأرض تُستخدم لتحصيل البيع فقط.');
                }
                $part = LandParcelPart::query()
                    ->where('land_parcel_id', (int) $parcel->id)
                    ->whereKey($partId)
                    ->first();
                if (! $part instanceof LandParcelPart) {
                    throw new \InvalidArgumentException('الجزء غير موجود على هذه الأرض.');
                }
                $remaining = $part->remainingTotal();
            } else {
                $remaining = $parcel->remainingTotal($side);
            }

            if ($amount > $remaining + 0.01) {
                throw new \InvalidArgumentException('المبلغ أكبر من المتبقي ('.$remaining.').');
            }

            $payment = LandParcelPayment::query()->create([
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
            ]);

            $this->cashboxLedgerService->syncFromLandParcelPayment($payment);

            if ($part instanceof LandParcelPart) {
                $this->maybeMarkPartSold($part->fresh() ?? $part);
            }
            $this->maybeMarkParcelSold($parcel->fresh() ?? $parcel);

            if ($side === LandParcelPayment::SIDE_SALE
                && ($payment->approval_status ?? '') === 'approved') {
                $payment->loadMissing('part:id,name');
                $this->distributeSaleToShareholders($parcel, $payment, $user);
            }

            return $payment->fresh(['landParcel', 'part', 'creator']) ?? $payment;
        });
    }

    public function delete(LandParcelPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $id = (int) $payment->id;
            $partId = $payment->land_parcel_part_id;
            $parcelId = (int) $payment->land_parcel_id;

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
        });
    }

    /**
     * توزيع تحصيل البيع على مساهمي الأرض حسب النسبة (بدون تكرار حركة الصندوق).
     */
    private function distributeSaleToShareholders(LandParcel $parcel, LandParcelPayment $payment, ?User $user): void
    {
        if (! Schema::hasTable('land_parcel_shareholder')) {
            return;
        }

        $members = LandParcelShareholder::query()
            ->with('shareholder:id,name')
            ->where('land_parcel_id', (int) $parcel->id)
            ->where('share_percentage', '>', 0)
            ->orderBy('id')
            ->get()
            ->filter(fn (LandParcelShareholder $m) => $m->shareholder !== null)
            ->values();

        if ($members->isEmpty()) {
            return;
        }

        $totalPct = round((float) $members->sum('share_percentage'), 4);
        if ($totalPct <= 0) {
            return;
        }

        $amount = round((float) $payment->amount, 2);
        $allocated = 0.0;
        $lastIndex = $members->count() - 1;
        $partLabel = $payment->part?->name;
        if ($partLabel === null && $payment->land_parcel_part_id) {
            $partLabel = LandParcelPart::query()->whereKey((int) $payment->land_parcel_part_id)->value('name');
        }

        foreach ($members as $index => $membership) {
            /** @var LandParcelShareholder $membership */
            $shareholder = $membership->shareholder;
            if (! $shareholder instanceof Shareholder) {
                continue;
            }

            if ($index === $lastIndex) {
                $shareAmount = round($amount - $allocated, 2);
            } else {
                $shareAmount = round($amount * ((float) $membership->share_percentage / $totalPct), 2);
                $allocated += $shareAmount;
            }

            if ($shareAmount <= 0) {
                continue;
            }

            $note = sprintf(
                'توزيع تحصيل بيع أرض «%s»%s — نسبة %.2f%% — دفعة #%d',
                $parcel->name,
                $partLabel ? ' / جزء: '.$partLabel : '',
                (float) $membership->share_percentage,
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

            $this->shareholderLedgerService->create($shareholder, $payload, $user);
        }
    }

    private function removeShareholderDistributions(int $paymentId): void
    {
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

        $entries = $query->get();
        foreach ($entries as $entry) {
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
