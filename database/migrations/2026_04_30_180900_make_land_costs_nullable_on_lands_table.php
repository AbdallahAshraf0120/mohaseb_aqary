<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('lands')) {
            return;
        }

        // Use raw SQL to avoid doctrine/dbal dependency for column changes.
        // MySQL/MariaDB syntax.
        $columns = [
            'land_cost',
            'building_license_cost',
            'piles_cost',
            'excavation_cost',
            'gravel_cost',
            'sand_cost',
            'cement_cost',
            'steel_cost',
            'carpentry_labor_cost',
            'blacksmith_labor_cost',
            'mason_labor_cost',
            'electrician_labor_cost',
            'tips_cost',
        ];

        foreach ($columns as $col) {
            DB::statement("ALTER TABLE `lands` MODIFY `{$col}` DECIMAL(14,2) NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('lands')) {
            return;
        }

        $columns = [
            'land_cost',
            'building_license_cost',
            'piles_cost',
            'excavation_cost',
            'gravel_cost',
            'sand_cost',
            'cement_cost',
            'steel_cost',
            'carpentry_labor_cost',
            'blacksmith_labor_cost',
            'mason_labor_cost',
            'electrician_labor_cost',
            'tips_cost',
        ];

        foreach ($columns as $col) {
            DB::statement("ALTER TABLE `lands` MODIFY `{$col}` DECIMAL(14,2) NOT NULL DEFAULT 0");
        }
    }
};

