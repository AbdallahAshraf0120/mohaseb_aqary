<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_parcel_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('land_parcel_id')->constrained('land_parcels')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('area_size', 12, 2)->nullable();
            $table->string('status', 20)->default('available'); // available | reserved | sold | cancelled
            $table->decimal('sale_price', 14, 2)->default(0);
            $table->date('sale_date')->nullable();
            $table->string('sold_to')->nullable();
            $table->string('sale_phone', 50)->nullable();
            $table->string('sale_payment_type', 20)->default('cash');
            $table->decimal('sale_down_payment', 14, 2)->default(0);
            $table->unsignedInteger('sale_installment_months')->nullable();
            $table->string('sale_installment_schedule', 20)->nullable();
            $table->date('sale_installment_start_date')->nullable();
            $table->json('sale_installment_plan')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['land_parcel_id', 'status']);
        });

        if (Schema::hasTable('land_parcel_payments') && ! Schema::hasColumn('land_parcel_payments', 'land_parcel_part_id')) {
            Schema::table('land_parcel_payments', function (Blueprint $table): void {
                $table->foreignId('land_parcel_part_id')
                    ->nullable()
                    ->after('land_parcel_id')
                    ->constrained('land_parcel_parts')
                    ->nullOnDelete();
                $table->index(['land_parcel_part_id', 'side']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('land_parcel_payments') && Schema::hasColumn('land_parcel_payments', 'land_parcel_part_id')) {
            Schema::table('land_parcel_payments', function (Blueprint $table): void {
                $table->dropForeign(['land_parcel_part_id']);
                $table->dropIndex(['land_parcel_part_id', 'side']);
                $table->dropColumn('land_parcel_part_id');
            });
        }

        Schema::dropIfExists('land_parcel_parts');
    }
};
