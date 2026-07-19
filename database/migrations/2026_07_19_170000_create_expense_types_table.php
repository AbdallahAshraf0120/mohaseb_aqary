<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
            $table->index(['project_id', 'sort_order']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_type_id')
                ->nullable()
                ->after('project_id')
                ->constrained('expense_types')
                ->nullOnDelete();
        });

        $this->backfillFromExistingCategories();
        $this->seedDefaultsForProjectsWithoutTypes();
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_type_id');
        });

        Schema::dropIfExists('expense_types');
    }

    private function backfillFromExistingCategories(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        $rows = DB::table('expenses')
            ->select('project_id', 'category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->get();

        $now = now();
        $typeIds = [];

        foreach ($rows as $row) {
            $projectId = (int) $row->project_id;
            $name = trim((string) $row->category);
            if ($name === '') {
                continue;
            }

            $key = $projectId.'|'.mb_strtolower($name);
            if (isset($typeIds[$key])) {
                continue;
            }

            $id = DB::table('expense_types')->insertGetId([
                'project_id' => $projectId,
                'name' => $name,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $typeIds[$key] = $id;
        }

        foreach ($typeIds as $key => $typeId) {
            [$projectId, ] = explode('|', $key, 2);
            $name = DB::table('expense_types')->where('id', $typeId)->value('name');
            DB::table('expenses')
                ->where('project_id', (int) $projectId)
                ->where('category', $name)
                ->update(['expense_type_id' => $typeId]);
        }
    }

    private function seedDefaultsForProjectsWithoutTypes(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        $defaults = [
            ['name' => 'مقاولات', 'sort_order' => 10],
            ['name' => 'كهرباء', 'sort_order' => 20],
            ['name' => 'مياه', 'sort_order' => 30],
            ['name' => 'صيانة', 'sort_order' => 40],
            ['name' => 'رواتب', 'sort_order' => 50],
            ['name' => 'تسويق', 'sort_order' => 60],
            ['name' => 'مصروفات إدارية', 'sort_order' => 70],
            ['name' => 'أخرى', 'sort_order' => 100],
        ];

        $now = now();
        $projectIds = DB::table('projects')->pluck('id');

        foreach ($projectIds as $projectId) {
            foreach ($defaults as $row) {
                $exists = DB::table('expense_types')
                    ->where('project_id', $projectId)
                    ->where('name', $row['name'])
                    ->exists();
                if ($exists) {
                    continue;
                }

                DB::table('expense_types')->insert([
                    'project_id' => $projectId,
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
