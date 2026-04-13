<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->unsignedInteger('ground_floor_shops_count')->default(0)->after('apartments_per_floor');
            $table->boolean('has_mezzanine')->default(false)->after('ground_floor_shops_count');
            $table->unsignedInteger('mezzanine_apartments_count')->default(0)->after('has_mezzanine');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn([
                'ground_floor_shops_count',
                'has_mezzanine',
                'mezzanine_apartments_count',
            ]);
        });
    }
};
