<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->unsignedInteger('floor_number')->default(1)->after('property_id');
            $table->string('apartment_model')->nullable()->after('floor_number');
            $table->string('payment_type')->default('cash')->after('sale_price');
            $table->decimal('down_payment', 14, 2)->default(0)->after('payment_type');
            $table->unsignedInteger('installment_months')->nullable()->after('down_payment');
            $table->date('installment_start_date')->nullable()->after('installment_months');
            $table->json('installment_plan')->nullable()->after('installment_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn([
                'floor_number',
                'apartment_model',
                'payment_type',
                'down_payment',
                'installment_months',
                'installment_start_date',
                'installment_plan',
            ]);
        });
    }
};
