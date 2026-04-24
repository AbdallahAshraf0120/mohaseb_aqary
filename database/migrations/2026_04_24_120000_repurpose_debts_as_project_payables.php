<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->dropForeign(['client_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE debts MODIFY client_id BIGINT UNSIGNED NULL');
        }

        Schema::table('debts', function (Blueprint $table): void {
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->string('creditor_name')->nullable()->after('client_id');
            $table->text('purchase_description')->nullable()->after('creditor_name');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table): void {
            $table->dropForeign(['client_id']);
        });

        $fallbackClientId = DB::table('clients')->orderBy('id')->value('id');
        if ($fallbackClientId !== null) {
            DB::table('debts')->whereNull('client_id')->update(['client_id' => $fallbackClientId]);
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE debts MODIFY client_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('debts', function (Blueprint $table): void {
            $table->dropColumn(['creditor_name', 'purchase_description']);
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
    }
};
