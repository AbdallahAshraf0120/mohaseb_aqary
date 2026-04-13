<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->json('mushaa_floors')->nullable()->after('mushaa_partner_name');
        });

        DB::table('properties')
            ->select('id', 'mezzanine_floors')
            ->whereNotNull('mezzanine_floors')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $decoded = json_decode((string) $row->mezzanine_floors, true);
                    if (! is_array($decoded)) {
                        continue;
                    }
                    $nums = collect($decoded)
                        ->filter(static fn ($item) => is_array($item) && filter_var($item['is_mushaa'] ?? false, FILTER_VALIDATE_BOOL))
                        ->map(static fn ($item) => (int) ($item['floor_number'] ?? 0))
                        ->filter(static fn (int $n) => $n >= 1)
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();
                    if ($nums === []) {
                        continue;
                    }
                    DB::table('properties')->where('id', $row->id)->update([
                        'mushaa_floors' => json_encode($nums, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn('mushaa_floors');
        });
    }
};
