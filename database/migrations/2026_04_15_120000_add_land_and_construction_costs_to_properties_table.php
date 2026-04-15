<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->string('land_name')->nullable()->after('property_type');
            $table->decimal('land_cost', 14, 2)->default(0)->after('owner_id');
            $table->decimal('building_license_cost', 14, 2)->default(0)->after('land_cost');
            $table->decimal('piles_cost', 14, 2)->default(0)->after('building_license_cost');
            $table->decimal('excavation_cost', 14, 2)->default(0)->after('piles_cost');
            $table->decimal('gravel_cost', 14, 2)->default(0)->after('excavation_cost');
            $table->decimal('sand_cost', 14, 2)->default(0)->after('gravel_cost');
            $table->decimal('cement_cost', 14, 2)->default(0)->after('sand_cost');
            $table->decimal('steel_cost', 14, 2)->default(0)->after('cement_cost');
            $table->decimal('carpentry_labor_cost', 14, 2)->default(0)->after('steel_cost');
            $table->decimal('blacksmith_labor_cost', 14, 2)->default(0)->after('carpentry_labor_cost');
            $table->decimal('mason_labor_cost', 14, 2)->default(0)->after('blacksmith_labor_cost');
            $table->decimal('electrician_labor_cost', 14, 2)->default(0)->after('mason_labor_cost');
            $table->decimal('tips_cost', 14, 2)->default(0)->after('electrician_labor_cost');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn([
                'land_name',
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
            ]);
        });
    }
};
