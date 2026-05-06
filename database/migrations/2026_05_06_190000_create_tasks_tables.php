<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open'); // open|in_progress|done|cancelled
            $table->string('priority')->default('normal'); // low|normal|high|urgent
            $table->dateTime('due_at')->nullable();

            $table->timestamps();

            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('task_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();

            $table->dateTime('happened_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_updates');
        Schema::dropIfExists('tasks');
    }
};

