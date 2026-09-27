<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->boolean('is_published')->default(false);
            $table->boolean('show_on_home')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('location')->nullable();
            $table->string('status_label')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->json('gallery')->nullable();
            $table->json('features')->nullable();
            $table->string('cta_label')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
            $table->index(['is_published', 'show_on_home']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_projects');
    }
};
