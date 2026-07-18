<?php

namespace App\Services;

use App\Models\LandParcel;
use App\Models\LandParcelPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
     *     notes?: string|null
     * }  $data
     */
    public function create(LandParcel $parcel, array $data, ?User $user): LandParcelPayment
    {
        return DB::transaction(function () use ($parcel, $data, $user): LandParcelPayment {
            $isAdmin = $user instanceof User && $user->isAdmin();
            $side = (string) $data['side'];
            $amount = round((float) $data['amount'], 2);
            $remaining = $parcel->remainingTotal($side);
            if ($amount > $remaining + 0.01) {
                throw new \InvalidArgumentException('المبلغ أكبر من المتبقي ('.$remaining.').');
            }

            $payment = LandParcelPayment::query()->create([
                'land_parcel_id' => (int) $parcel->id,
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
            $this->maybeMarkSold($parcel->fresh() ?? $parcel);

            return $payment->fresh(['landParcel', 'creator']) ?? $payment;
        });
    }

    public function delete(LandParcelPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $id = (int) $payment->id;
            $payment->delete();
            $this->cashboxLedgerService->removeLandParcelPayment($id);
        });
    }

    private function maybeMarkSold(LandParcel $parcel): void
    {
        $salePrice = (float) ($parcel->sale_price ?? 0);
        if ($salePrice <= 0) {
            return;
        }

        if ($parcel->remainingTotal(LandParcelPayment::SIDE_SALE) <= 0.01
            && $parcel->status !== 'sold') {
            $parcel->update(['status' => 'sold']);
        }
    }
}
