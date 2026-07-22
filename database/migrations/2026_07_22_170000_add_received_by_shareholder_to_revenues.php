<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('revenues') && ! Schema::hasColumn('revenues', 'received_by_shareholder_id')) {
            Schema::table('revenues', function (Blueprint $table): void {
                $table->foreignId('received_by_shareholder_id')
                    ->nullable()
                    ->after('payment_method')
                    ->constrained('shareholders')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('shareholder_ledger_entries')
            && Schema::hasTable('revenues')
            && ! Schema::hasColumn('shareholder_ledger_entries', 'revenue_id')) {
            Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
                $table->foreignId('revenue_id')
                    ->nullable()
                    ->after('land_parcel_payment_id')
                    ->constrained('revenues')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shareholder_ledger_entries')
            && Schema::hasColumn('shareholder_ledger_entries', 'revenue_id')) {
            Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('revenue_id');
            });
        }

        if (Schema::hasTable('revenues')
            && Schema::hasColumn('revenues', 'received_by_shareholder_id')) {
            Schema::table('revenues', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('received_by_shareholder_id');
            });
        }
    }
};
