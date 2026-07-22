<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales') && ! Schema::hasColumn('sales', 'received_by_shareholder_id')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->foreignId('received_by_shareholder_id')
                    ->nullable()
                    ->after('down_payment')
                    ->constrained('shareholders')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('shareholder_ledger_entries')
            && Schema::hasTable('sales')
            && ! Schema::hasColumn('shareholder_ledger_entries', 'sale_id')) {
            Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
                $table->foreignId('sale_id')
                    ->nullable()
                    ->after('revenue_id')
                    ->constrained('sales')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shareholder_ledger_entries')
            && Schema::hasColumn('shareholder_ledger_entries', 'sale_id')) {
            Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('sale_id');
            });
        }

        if (Schema::hasTable('sales')
            && Schema::hasColumn('sales', 'received_by_shareholder_id')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('received_by_shareholder_id');
            });
        }
    }
};
