<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table): void {
            $table->string('approval_status', 16)->default('approved')->after('description');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable()->after('rejected_by');
            $table->index(['approval_status', 'type']);
        });

        Schema::table('revenues', function (Blueprint $table): void {
            $table->string('approval_status', 16)->default('approved')->after('notes');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable()->after('rejected_by');
            $table->index(['approval_status', 'paid_at']);
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->string('approval_status', 16)->default('approved')->after('description');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable()->after('rejected_by');
            $table->index(['approval_status', 'created_at']);
        });

        Schema::table('debt_payments', function (Blueprint $table): void {
            $table->string('approval_status', 16)->default('approved')->after('note');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable()->after('rejected_by');
            $table->index(['approval_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('debt_payments', function (Blueprint $table): void {
            $table->dropIndex(['approval_status', 'created_at']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['approval_status', 'approved_at', 'rejected_at', 'rejection_reason']);
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex(['approval_status', 'created_at']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['approval_status', 'approved_at', 'rejected_at', 'rejection_reason']);
        });

        Schema::table('revenues', function (Blueprint $table): void {
            $table->dropIndex(['approval_status', 'paid_at']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['approval_status', 'approved_at', 'rejected_at', 'rejection_reason']);
        });

        Schema::table('treasury_transactions', function (Blueprint $table): void {
            $table->dropIndex(['approval_status', 'type']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['approval_status', 'approved_at', 'rejected_at', 'rejection_reason']);
        });
    }
};

