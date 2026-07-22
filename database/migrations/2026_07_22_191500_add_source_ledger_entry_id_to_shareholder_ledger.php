<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shareholder_ledger_entries')) {
            return;
        }

        Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('shareholder_ledger_entries', 'source_ledger_entry_id')) {
                $table->foreignId('source_ledger_entry_id')
                    ->nullable()
                    ->after('sale_id')
                    ->constrained('shareholder_ledger_entries')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shareholder_ledger_entries')) {
            return;
        }

        Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('shareholder_ledger_entries', 'source_ledger_entry_id')) {
                $table->dropConstrainedForeignId('source_ledger_entry_id');
            }
        });
    }
};
