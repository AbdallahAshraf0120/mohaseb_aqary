<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fund_transfers')) {
            return;
        }

        Schema::create('fund_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('from_type', 32); // project | land_cashbox
            $table->unsignedBigInteger('from_id');
            $table->string('to_type', 32);
            $table->unsignedBigInteger('to_id');
            $table->decimal('amount', 14, 2);
            $table->date('transferred_at');
            $table->text('notes')->nullable();
            $table->foreignId('shareholder_id')->nullable()->constrained('shareholders')->nullOnDelete();
            $table->foreignId('source_land_parcel_id')->nullable()->constrained('land_parcels')->nullOnDelete();
            $table->foreignId('from_treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->foreignId('to_treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['from_type', 'from_id']);
            $table->index(['to_type', 'to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
    }
};
