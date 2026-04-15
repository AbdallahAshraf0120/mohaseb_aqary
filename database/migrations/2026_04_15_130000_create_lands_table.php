<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('land_cost', 14, 2)->default(0);
            $table->decimal('building_license_cost', 14, 2)->default(0);
            $table->decimal('piles_cost', 14, 2)->default(0);
            $table->decimal('excavation_cost', 14, 2)->default(0);
            $table->decimal('gravel_cost', 14, 2)->default(0);
            $table->decimal('sand_cost', 14, 2)->default(0);
            $table->decimal('cement_cost', 14, 2)->default(0);
            $table->decimal('steel_cost', 14, 2)->default(0);
            $table->decimal('carpentry_labor_cost', 14, 2)->default(0);
            $table->decimal('blacksmith_labor_cost', 14, 2)->default(0);
            $table->decimal('mason_labor_cost', 14, 2)->default(0);
            $table->decimal('electrician_labor_cost', 14, 2)->default(0);
            $table->decimal('tips_cost', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lands');
    }
};
