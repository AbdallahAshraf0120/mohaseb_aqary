<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('land_parcel_payments')) {
            Schema::table('land_parcel_payments', function (Blueprint $table): void {
                if (! Schema::hasColumn('land_parcel_payments', 'paid_by_shareholder_id')) {
                    $table->foreignId('paid_by_shareholder_id')
                        ->nullable()
                        ->after('created_by')
                        ->constrained('shareholders')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('land_parcel_payments', 'distribution_status')) {
                    $table->string('distribution_status', 20)->default('none')->after('paid_by_shareholder_id');
                    // none | pending | distributed (sale only uses pending/distributed)
                }
            });
        }

        if (! Schema::hasTable('land_parcel_payment_distributions')) {
            Schema::create('land_parcel_payment_distributions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('land_parcel_payment_id')->constrained('land_parcel_payments')->cascadeOnDelete();
                $table->foreignId('shareholder_id')->constrained('shareholders')->cascadeOnDelete();
                $table->decimal('amount', 14, 2);
                $table->string('basis', 20); // planned | actual | manual | legacy
                $table->decimal('percentage_used', 8, 2)->nullable();
                $table->foreignId('ledger_entry_id')->nullable()->constrained('shareholder_ledger_entries')->nullOnDelete();
                $table->timestamps();
                $table->index(['land_parcel_payment_id', 'shareholder_id'], 'lppd_payment_shareholder_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('land_parcel_payment_distributions');

        if (Schema::hasTable('land_parcel_payments')) {
            Schema::table('land_parcel_payments', function (Blueprint $table): void {
                if (Schema::hasColumn('land_parcel_payments', 'distribution_status')) {
                    $table->dropColumn('distribution_status');
                }
                if (Schema::hasColumn('land_parcel_payments', 'paid_by_shareholder_id')) {
                    $table->dropConstrainedForeignId('paid_by_shareholder_id');
                }
            });
        }
    }
};
