<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_parcels', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->decimal('area_size', 12, 2)->nullable(); // م²
            $table->string('deed_number')->nullable();

            // owned | for_sale | reserved | sold | cancelled
            $table->string('status')->default('owned');

            $table->decimal('purchase_price', 14, 2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->string('purchased_from')->nullable();
            $table->string('purchase_phone')->nullable();

            $table->decimal('sale_price', 14, 2)->nullable();
            $table->date('sale_date')->nullable();
            $table->string('sold_to')->nullable();
            $table->string('sale_phone')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('purchase_date');
            $table->index('sale_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_parcels');
    }
};
