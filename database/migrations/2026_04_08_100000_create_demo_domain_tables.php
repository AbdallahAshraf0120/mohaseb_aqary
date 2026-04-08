<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('viewer')->after('password');
        });

        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->decimal('price', 14, 2);
            $table->string('status')->default('available');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('national_id')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_price', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->decimal('sale_price', 14, 2);
            $table->date('sale_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('treasury_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('type'); // revenue|expense
            $table->decimal('amount', 14, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('revenues', function (Blueprint $table): void {
            $table->id();
            $table->decimal('amount', 14, 2);
            $table->string('category')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->decimal('amount', 14, 2);
            $table->string('category')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('debts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('shareholders', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->decimal('share_percentage', 5, 2);
            $table->decimal('total_investment', 14, 2)->default(0);
            $table->decimal('profit_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name')->default('Real Estate Demo');
            $table->string('currency')->default('EGP');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('shareholders');
        Schema::dropIfExists('debts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('revenues');
        Schema::dropIfExists('treasury_transactions');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('properties');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
