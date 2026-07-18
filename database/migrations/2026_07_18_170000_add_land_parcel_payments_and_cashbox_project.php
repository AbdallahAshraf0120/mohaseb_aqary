<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'is_land_trading_cashbox')) {
                $table->boolean('is_land_trading_cashbox')->default(false)->after('is_draft');
            }
        });

        Schema::table('land_parcels', function (Blueprint $table): void {
            $table->string('purchase_payment_type', 20)->default('cash')->after('purchase_phone');
            $table->decimal('purchase_down_payment', 14, 2)->default(0)->after('purchase_payment_type');
            $table->unsignedInteger('purchase_installment_months')->nullable()->after('purchase_down_payment');
            $table->string('purchase_installment_schedule', 20)->nullable()->after('purchase_installment_months');
            $table->date('purchase_installment_start_date')->nullable()->after('purchase_installment_schedule');
            $table->json('purchase_installment_plan')->nullable()->after('purchase_installment_start_date');

            $table->string('sale_payment_type', 20)->nullable()->after('sale_phone');
            $table->decimal('sale_down_payment', 14, 2)->nullable()->after('sale_payment_type');
            $table->unsignedInteger('sale_installment_months')->nullable()->after('sale_down_payment');
            $table->string('sale_installment_schedule', 20)->nullable()->after('sale_installment_months');
            $table->date('sale_installment_start_date')->nullable()->after('sale_installment_schedule');
            $table->json('sale_installment_plan')->nullable()->after('sale_installment_start_date');
        });

        Schema::create('land_parcel_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('land_parcel_id')->constrained('land_parcels')->cascadeOnDelete();
            $table->string('side', 20); // purchase | sale
            $table->string('kind', 30)->default('other'); // down_payment | installment | secondary | other
            $table->decimal('amount', 14, 2);
            $table->date('paid_at');
            $table->string('payment_method', 30)->default('cash');
            $table->text('notes')->nullable();
            $table->string('approval_status', 20)->default('approved');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['land_parcel_id', 'side']);
            $table->index(['approval_status', 'paid_at']);
        });

        $exists = DB::table('projects')->where('is_land_trading_cashbox', true)->exists();
        if (! $exists) {
            $row = [
                'name' => 'أراضي البيع والشراء',
                'code' => 'LAND-TRADING',
                'is_active' => true,
                'is_land_trading_cashbox' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('projects', 'is_draft')) {
                $row['is_draft'] = false;
            }
            if (Schema::hasColumn('projects', 'capital')) {
                $row['capital'] = 0;
            }
            DB::table('projects')->insert($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('land_parcel_payments');

        Schema::table('land_parcels', function (Blueprint $table): void {
            $table->dropColumn([
                'purchase_payment_type',
                'purchase_down_payment',
                'purchase_installment_months',
                'purchase_installment_schedule',
                'purchase_installment_start_date',
                'purchase_installment_plan',
                'sale_payment_type',
                'sale_down_payment',
                'sale_installment_months',
                'sale_installment_schedule',
                'sale_installment_start_date',
                'sale_installment_plan',
            ]);
        });

        Schema::table('projects', function (Blueprint $table): void {
            if (Schema::hasColumn('projects', 'is_land_trading_cashbox')) {
                $table->dropColumn('is_land_trading_cashbox');
            }
        });
    }
};
