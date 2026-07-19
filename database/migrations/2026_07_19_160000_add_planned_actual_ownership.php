<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table): void {
                if (! Schema::hasColumn('projects', 'planned_capital')) {
                    $table->decimal('planned_capital', 14, 2)->default(0)->after('capital');
                }
                if (! Schema::hasColumn('projects', 'actual_capital')) {
                    $table->decimal('actual_capital', 14, 2)->default(0)->after('planned_capital');
                }
            });

            DB::table('projects')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $capital = round((float) ($row->capital ?? 0), 2);
                    DB::table('projects')->where('id', $row->id)->update([
                        'planned_capital' => $capital,
                        'actual_capital' => $capital,
                    ]);
                }
            });
        }

        if (Schema::hasTable('land_parcels')) {
            Schema::table('land_parcels', function (Blueprint $table): void {
                if (! Schema::hasColumn('land_parcels', 'planned_capital')) {
                    $table->decimal('planned_capital', 14, 2)->default(0)->after('purchase_price');
                }
                if (! Schema::hasColumn('land_parcels', 'actual_capital')) {
                    $table->decimal('actual_capital', 14, 2)->default(0)->after('planned_capital');
                }
            });

            DB::table('land_parcels')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $price = round((float) ($row->purchase_price ?? 0), 2);
                    DB::table('land_parcels')->where('id', $row->id)->update([
                        'planned_capital' => $price,
                        'actual_capital' => $price,
                    ]);
                }
            });
        }

        if (Schema::hasTable('project_shareholder')) {
            Schema::table('project_shareholder', function (Blueprint $table): void {
                if (! Schema::hasColumn('project_shareholder', 'planned_investment')) {
                    $table->decimal('planned_investment', 14, 2)->default(0)->after('total_investment');
                }
                if (! Schema::hasColumn('project_shareholder', 'planned_percentage')) {
                    $table->decimal('planned_percentage', 8, 2)->default(0)->after('planned_investment');
                }
                if (! Schema::hasColumn('project_shareholder', 'actual_investment')) {
                    $table->decimal('actual_investment', 14, 2)->default(0)->after('planned_percentage');
                }
                if (! Schema::hasColumn('project_shareholder', 'actual_percentage')) {
                    $table->decimal('actual_percentage', 8, 2)->default(0)->after('actual_investment');
                }
            });

            DB::table('project_shareholder')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $inv = round((float) ($row->total_investment ?? 0), 2);
                    $pct = round((float) ($row->share_percentage ?? 0), 2);
                    DB::table('project_shareholder')->where('id', $row->id)->update([
                        'planned_investment' => $inv,
                        'planned_percentage' => $pct,
                        'actual_investment' => $inv,
                        'actual_percentage' => $pct,
                    ]);
                }
            });
        }

        if (Schema::hasTable('land_parcel_shareholder')) {
            Schema::table('land_parcel_shareholder', function (Blueprint $table): void {
                if (! Schema::hasColumn('land_parcel_shareholder', 'planned_investment')) {
                    $table->decimal('planned_investment', 14, 2)->default(0)->after('total_investment');
                }
                if (! Schema::hasColumn('land_parcel_shareholder', 'planned_percentage')) {
                    $table->decimal('planned_percentage', 8, 2)->default(0)->after('planned_investment');
                }
                if (! Schema::hasColumn('land_parcel_shareholder', 'actual_investment')) {
                    $table->decimal('actual_investment', 14, 2)->default(0)->after('planned_percentage');
                }
                if (! Schema::hasColumn('land_parcel_shareholder', 'actual_percentage')) {
                    $table->decimal('actual_percentage', 8, 2)->default(0)->after('actual_investment');
                }
            });

            DB::table('land_parcel_shareholder')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $inv = round((float) ($row->total_investment ?? 0), 2);
                    $pct = round((float) ($row->share_percentage ?? 0), 2);
                    DB::table('land_parcel_shareholder')->where('id', $row->id)->update([
                        'planned_investment' => $inv,
                        'planned_percentage' => $pct,
                        'actual_investment' => $inv,
                        'actual_percentage' => $pct,
                    ]);
                }
            });
        }

        if (! Schema::hasTable('ownership_snapshots')) {
            Schema::create('ownership_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->string('target_type', 32); // project | land_parcel
                $table->unsignedBigInteger('target_id');
                $table->string('kind', 40)->default('adopt_plan');
                $table->json('before');
                $table->json('after');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['target_type', 'target_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_snapshots');

        if (Schema::hasTable('land_parcel_shareholder')) {
            Schema::table('land_parcel_shareholder', function (Blueprint $table): void {
                foreach (['actual_percentage', 'actual_investment', 'planned_percentage', 'planned_investment'] as $col) {
                    if (Schema::hasColumn('land_parcel_shareholder', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('project_shareholder')) {
            Schema::table('project_shareholder', function (Blueprint $table): void {
                foreach (['actual_percentage', 'actual_investment', 'planned_percentage', 'planned_investment'] as $col) {
                    if (Schema::hasColumn('project_shareholder', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('land_parcels')) {
            Schema::table('land_parcels', function (Blueprint $table): void {
                foreach (['actual_capital', 'planned_capital'] as $col) {
                    if (Schema::hasColumn('land_parcels', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table): void {
                foreach (['actual_capital', 'planned_capital'] as $col) {
                    if (Schema::hasColumn('projects', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
