<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales', 'shareholder_down_payment_amount')) {
                $table->decimal('shareholder_down_payment_amount', 14, 2)
                    ->nullable()
                    ->after('received_by_shareholder_id');
            }
        });

        // ترحيل قديم: لو المقدم منسوب لمساهم بالكامل، اعتبر المبلغ كله لحساب المساهم
        if (Schema::hasColumn('sales', 'received_by_shareholder_id')
            && Schema::hasColumn('sales', 'shareholder_down_payment_amount')) {
            DB::table('sales')
                ->whereNotNull('received_by_shareholder_id')
                ->whereNull('shareholder_down_payment_amount')
                ->update([
                    'shareholder_down_payment_amount' => DB::raw('down_payment'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table): void {
            if (Schema::hasColumn('sales', 'shareholder_down_payment_amount')) {
                $table->dropColumn('shareholder_down_payment_amount');
            }
        });
    }
};
