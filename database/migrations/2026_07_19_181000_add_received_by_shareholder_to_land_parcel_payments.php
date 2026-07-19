<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('land_parcel_payments')) {
            return;
        }

        Schema::table('land_parcel_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('land_parcel_payments', 'received_by_shareholder_id')) {
                $table->foreignId('received_by_shareholder_id')
                    ->nullable()
                    ->after('paid_by_shareholder_id')
                    ->constrained('shareholders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('land_parcel_payments')) {
            return;
        }

        Schema::table('land_parcel_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('land_parcel_payments', 'received_by_shareholder_id')) {
                $table->dropConstrainedForeignId('received_by_shareholder_id');
            }
        });
    }
};
