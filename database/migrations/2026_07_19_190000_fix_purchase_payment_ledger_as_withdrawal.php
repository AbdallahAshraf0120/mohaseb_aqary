<?php

use App\Models\LandParcelPayment;
use App\Models\ShareholderLedgerEntry;
use App\Services\OwnershipService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * سداد الشراء كان يُسجَّل خطأً كـ«إيداع رأس مال» فيزيد الجاري.
 * يحوّله إلى «سحب» (مدين) حتى ينقص رصيد المساهم.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shareholder_ledger_entries')
            || ! Schema::hasTable('land_parcel_payments')
            || ! Schema::hasColumn('shareholder_ledger_entries', 'land_parcel_payment_id')) {
            return;
        }

        $purchasePaymentIds = LandParcelPayment::query()
            ->where('side', LandParcelPayment::SIDE_PURCHASE)
            ->pluck('id');

        if ($purchasePaymentIds->isEmpty()) {
            return;
        }

        $entries = ShareholderLedgerEntry::withoutProjectScope()
            ->where('type', ShareholderLedgerEntry::TYPE_CAPITAL)
            ->whereIn('land_parcel_payment_id', $purchasePaymentIds)
            ->get(['id', 'land_parcel_id']);

        if ($entries->isEmpty()) {
            return;
        }

        ShareholderLedgerEntry::withoutProjectScope()
            ->whereIn('id', $entries->pluck('id'))
            ->update([
                'type' => ShareholderLedgerEntry::TYPE_WITHDRAWAL,
                'direction' => ShareholderLedgerEntry::DIRECTION_DEBIT,
            ]);

        $parcelIds = $entries->pluck('land_parcel_id')->filter()->unique()->values();
        if ($parcelIds->isEmpty() || ! Schema::hasColumn('land_parcel_shareholder', 'actual_investment')) {
            return;
        }

        $ownership = app(OwnershipService::class);
        foreach ($parcelIds as $parcelId) {
            $ownership->syncLandActual((int) $parcelId);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('shareholder_ledger_entries')
            || ! Schema::hasTable('land_parcel_payments')
            || ! Schema::hasColumn('shareholder_ledger_entries', 'land_parcel_payment_id')) {
            return;
        }

        $purchasePaymentIds = DB::table('land_parcel_payments')
            ->where('side', 'purchase')
            ->pluck('id');

        if ($purchasePaymentIds->isEmpty()) {
            return;
        }

        ShareholderLedgerEntry::withoutProjectScope()
            ->where('type', ShareholderLedgerEntry::TYPE_WITHDRAWAL)
            ->whereIn('land_parcel_payment_id', $purchasePaymentIds)
            ->where('notes', 'like', 'سداد شراء أرض%')
            ->update([
                'type' => ShareholderLedgerEntry::TYPE_CAPITAL,
                'direction' => ShareholderLedgerEntry::DIRECTION_CREDIT,
            ]);
    }
};
