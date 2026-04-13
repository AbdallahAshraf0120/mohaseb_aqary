<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->unsignedInteger('building_total_floors')->default(1)->after('property_type');
            $table->json('registered_floors')->nullable()->after('floors_count');
        });

        DB::table('properties')
            ->select('id', 'floors_count')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $floors = max(1, (int) ($row->floors_count ?? 1));
                    DB::table('properties')
                        ->where('id', $row->id)
                        ->update([
                            'building_total_floors' => $floors,
                            'registered_floors' => json_encode(range(1, $floors), JSON_UNESCAPED_UNICODE),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn([
                'building_total_floors',
                'registered_floors',
            ]);
        });
    }
};
