<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - نوع الوحدات في العقار (شقق في عمارة / فيلات / تاون هاوس / قطع أراضي / محلات …).
 * - دورة حياة البيعة: إلغاء التعاقد واسترداد الفلوس، والتنازل لعميل تاني، والحجز بعربون.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('properties', 'unit_kind')) {
            Schema::table('properties', function (Blueprint $table): void {
                $table->string('unit_kind', 20)->default('apartment')->after('property_type');
            });
        }

        Schema::table('sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('rejection_reason');
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
                $table->string('cancellation_reason', 500)->nullable()->after('cancelled_by');
                $table->decimal('refund_amount', 20, 5)->nullable()->after('cancellation_reason');
            }
        });

        if (! Schema::hasTable('sale_transfers')) {
            Schema::create('sale_transfers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('sale_id')->index();
                $table->unsignedBigInteger('from_client_id');
                $table->unsignedBigInteger('to_client_id');
                $table->decimal('fee', 20, 5)->default(0);
                $table->date('transferred_at');
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('property_id');
                $table->unsignedSmallInteger('floor_number')->default(1);
                $table->boolean('is_mezzanine')->default(false);
                $table->unsignedSmallInteger('unit_number');
                $table->unsignedBigInteger('client_id');
                $table->decimal('deposit', 20, 5)->default(0);
                $table->unsignedBigInteger('receipt_no')->nullable();
                $table->date('reserved_at');
                $table->date('expires_at')->nullable();
                // active / converted / cancelled
                $table->string('status', 20)->default('active');
                $table->string('deposit_outcome', 20)->nullable(); // refunded / forfeited (عند الإلغاء)
                $table->unsignedBigInteger('sale_id')->nullable();
                $table->string('notes', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['project_id', 'property_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('sale_transfers');
        Schema::table('sales', function (Blueprint $table): void {
            foreach (['cancelled_at', 'cancelled_by', 'cancellation_reason', 'refund_amount'] as $c) {
                if (Schema::hasColumn('sales', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
        if (Schema::hasColumn('properties', 'unit_kind')) {
            Schema::table('properties', fn (Blueprint $t) => $t->dropColumn('unit_kind'));
        }
    }
};
