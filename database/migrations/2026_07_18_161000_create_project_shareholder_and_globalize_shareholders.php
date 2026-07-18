<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_shareholder', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('shareholder_id')->constrained('shareholders')->cascadeOnDelete();
            $table->decimal('share_percentage', 5, 2)->default(0);
            $table->decimal('total_investment', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'shareholder_id']);
        });

        if (Schema::hasColumn('shareholders', 'project_id')) {
            $rows = DB::table('shareholders')->select([
                'id',
                'project_id',
                'share_percentage',
                'total_investment',
                'created_at',
                'updated_at',
            ])->get();

            foreach ($rows as $row) {
                if ($row->project_id === null) {
                    continue;
                }
                DB::table('project_shareholder')->insert([
                    'project_id' => (int) $row->project_id,
                    'shareholder_id' => (int) $row->id,
                    'share_percentage' => $row->share_percentage ?? 0,
                    'total_investment' => $row->total_investment ?? 0,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }

            Schema::table('shareholders', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('project_id');
                $table->dropColumn(['share_percentage', 'total_investment', 'profit_amount']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('shareholders', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('projects')->nullOnDelete();
            $table->decimal('share_percentage', 5, 2)->default(0)->after('name');
            $table->decimal('total_investment', 14, 2)->default(0)->after('share_percentage');
            $table->decimal('profit_amount', 14, 2)->default(0)->after('total_investment');
        });

        $memberships = DB::table('project_shareholder')->orderBy('id')->get();
        foreach ($memberships as $m) {
            DB::table('shareholders')->where('id', $m->shareholder_id)->update([
                'project_id' => $m->project_id,
                'share_percentage' => $m->share_percentage,
                'total_investment' => $m->total_investment,
            ]);
        }

        Schema::dropIfExists('project_shareholder');
    }
};
