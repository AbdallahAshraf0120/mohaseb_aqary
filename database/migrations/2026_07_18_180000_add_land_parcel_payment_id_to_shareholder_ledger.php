<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shareholder_ledger_entries')) {
            return;
        }

        Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('shareholder_ledger_entries', 'land_parcel_payment_id')) {
                $table->foreignId('land_parcel_payment_id')
                    ->nullable()
                    ->after('land_parcel_id')
                    ->constrained('land_parcel_payments')
                    ->nullOnDelete();
                $table->index('land_parcel_payment_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shareholder_ledger_entries')) {
            return;
        }

        Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('shareholder_ledger_entries', 'land_parcel_payment_id')) {
                $table->dropForeign(['land_parcel_payment_id']);
                $table->dropIndex(['land_parcel_payment_id']);
                $table->dropColumn('land_parcel_payment_id');
            }
        });
    }
};
