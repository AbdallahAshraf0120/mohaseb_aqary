<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('revenues', function (Blueprint $table): void {
            $table->foreignId('contract_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->after('contract_id')->constrained('sales')->nullOnDelete();
            $table->date('paid_at')->nullable()->after('source');
            $table->string('payment_method')->default('cash')->after('paid_at');
            $table->text('notes')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contract_id');
            $table->dropConstrainedForeignId('sale_id');
            $table->dropColumn(['paid_at', 'payment_method', 'notes']);
        });
    }
};
