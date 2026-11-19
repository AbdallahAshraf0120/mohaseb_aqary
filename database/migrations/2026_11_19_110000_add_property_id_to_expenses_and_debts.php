<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ربط المصروف وذمة المورد بعقار معيّن (اختياري) عشان ربح كل عمارة. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['expenses', 'debts'] as $t) {
            if (! Schema::hasColumn($t, 'property_id')) {
                Schema::table($t, function (Blueprint $table): void {
                    $table->unsignedBigInteger('property_id')->nullable()->after('project_id');
                    $table->index('property_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['expenses', 'debts'] as $t) {
            if (Schema::hasColumn($t, 'property_id')) {
                Schema::table($t, function (Blueprint $table): void {
                    $table->dropIndex(['property_id']);
                    $table->dropColumn('property_id');
                });
            }
        }
    }
};
