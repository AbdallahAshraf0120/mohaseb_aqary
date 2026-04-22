<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'code']);
        });

        $defaults = [
            ['code' => 'normal', 'name' => 'عادية', 'sort_order' => 0],
            ['code' => 'facade', 'name' => 'واجهة', 'sort_order' => 10],
            ['code' => 'corner', 'name' => 'ناصية', 'sort_order' => 20],
        ];
        $now = now();
        foreach (DB::table('projects')->pluck('id') as $projectId) {
            foreach ($defaults as $row) {
                DB::table('facings')->insert([
                    'project_id' => (int) $projectId,
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facings');
    }
};
