<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_sketch_cells', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();

            // مفتاح الخلية على المخطط: مثل floor-3:slot-2 أو ground:shop-1 أو mezz-2:slot-1
            $table->string('cell_key', 64);

            // الحالة اليدوية التي يضبطها المستخدم على الخلية:
            //   available | sold | pending | reserved | viewing | blocked
            $table->string('status', 32);

            $table->string('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['property_id', 'cell_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_sketch_cells');
    }
};
