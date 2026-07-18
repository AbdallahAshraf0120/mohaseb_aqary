<?php

namespace App\Services;

use App\Models\LandParcel;
use App\Models\LandParcelPart;
use App\Models\LandParcelPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LandParcelPaymentService
{
    public function __construct(
        private readonly CashboxLedgerService $cashboxLedgerService,
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

            return $payment->fresh(['landParcel', 'part', 'creator']) ?? $payment;
        });
    }

    public function delete(LandParcelPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $id = (int) $payment->id;
            $partId = $payment->land_parcel_part_id;
            $parcelId = (int) $payment->land_parcel_id;
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
