<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->string('property_type')->nullable()->after('name');
            $table->unsignedInteger('floors_count')->default(1)->after('property_type');
            $table->unsignedInteger('apartments_per_floor')->default(1)->after('floors_count');
            $table->unsignedInteger('total_apartments')->default(1)->after('apartments_per_floor');
            $table->json('shareholder_allocations')->nullable()->after('total_apartments');
            $table->json('apartment_models')->nullable()->after('shareholder_allocations');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn([
                'property_type',
                'floors_count',
                'apartments_per_floor',
                'total_apartments',
                'shareholder_allocations',
                'apartment_models',
            ]);
        });
    }
};
