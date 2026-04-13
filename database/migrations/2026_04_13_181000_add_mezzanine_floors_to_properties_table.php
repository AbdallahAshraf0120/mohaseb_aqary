<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->json('mezzanine_floors')->nullable()->after('registered_floors');
        });

        DB::table('properties')
            ->where('has_mezzanine', true)
            ->where('mezzanine_apartments_count', '>', 0)
            ->whereNull('mezzanine_floors')
            ->update([
                'mezzanine_floors' => DB::raw("JSON_ARRAY(JSON_OBJECT('floor_number', 1, 'apartments_count', mezzanine_apartments_count))"),
            ]);
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn('mezzanine_floors');
        });
    }
};
