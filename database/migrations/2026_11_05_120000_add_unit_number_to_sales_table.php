<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رقم الشقة داخل الدور. قبل كده البيعة كانت متعرّفة بالدور + النموذج بس،
 * فما كانش ينفع تبيع شقتين بنفس النموذج في نفس الدور.
 * المبيعات القديمة هتفضل null لحد ما تتعدّل.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'unit_number')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->unsignedSmallInteger('unit_number')->nullable()->after('is_mezzanine');
                $table->index(['property_id', 'floor_number', 'is_mezzanine', 'unit_number'], 'sales_unit_lookup');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'unit_number')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->dropIndex('sales_unit_lookup');
                $table->dropColumn('unit_number');
            });
        }
    }
};
