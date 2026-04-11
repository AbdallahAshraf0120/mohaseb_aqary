<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('projects')->insert([
            'name' => 'المشروع الافتراضي',
            'code' => 'default',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $defaultProjectId = (int) DB::table('projects')->where('code', 'default')->value('id');

        Schema::table('areas', function (Blueprint $table): void {
            $table->dropUnique(['name']);
        });

        $tables = [
            'areas',
            'properties',
            'clients',
            'contracts',
            'sales',
            'treasury_transactions',
            'revenues',
            'expenses',
            'debts',
            'shareholders',
            'settings',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($defaultProjectId): void {
                $table->foreignId('project_id')
                    ->default($defaultProjectId)
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            });
        }

        Schema::table('areas', function (Blueprint $table): void {
            $table->unique(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table): void {
            $table->dropUnique(['project_id', 'name']);
        });

        $tables = [
            'settings',
            'shareholders',
            'debts',
            'expenses',
            'revenues',
            'treasury_transactions',
            'sales',
            'contracts',
            'clients',
            'properties',
            'areas',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            });
        }

        Schema::table('areas', function (Blueprint $table): void {
            $table->unique('name');
        });

        Schema::dropIfExists('projects');
    }
};
