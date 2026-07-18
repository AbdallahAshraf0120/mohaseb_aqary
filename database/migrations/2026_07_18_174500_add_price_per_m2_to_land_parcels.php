<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_parcels', function (Blueprint $table): void {
            if (! Schema::hasColumn('land_parcels', 'purchase_price_per_m2')) {
                $table->decimal('purchase_price_per_m2', 14, 2)->nullable()->after('area_size');
            }
            if (! Schema::hasColumn('land_parcels', 'sale_price_per_m2')) {
                $table->decimal('sale_price_per_m2', 14, 2)->nullable()->after('purchase_price_per_m2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('land_parcels', function (Blueprint $table): void {
            $cols = [];
            if (Schema::hasColumn('land_parcels', 'purchase_price_per_m2')) {
                $cols[] = 'purchase_price_per_m2';
            }
            if (Schema::hasColumn('land_parcels', 'sale_price_per_m2')) {
                $cols[] = 'sale_price_per_m2';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
