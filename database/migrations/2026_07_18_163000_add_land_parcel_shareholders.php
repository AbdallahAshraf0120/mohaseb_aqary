<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_parcel_shareholder', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('land_parcel_id')->constrained('land_parcels')->cascadeOnDelete();
            $table->foreignId('shareholder_id')->constrained('shareholders')->cascadeOnDelete();
            $table->decimal('share_percentage', 5, 2)->default(0);
            $table->decimal('total_investment', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['land_parcel_id', 'shareholder_id']);
        });

        Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
            $table->unsignedBigInteger('land_parcel_id')->nullable()->after('project_id');
            $table->foreign('land_parcel_id')->references('id')->on('land_parcels')->nullOnDelete();
            $table->index(['land_parcel_id', 'type']);
        });

        // السماح بحركات جاري للأراضي بدون مشروع
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE shareholder_ledger_entries MODIFY project_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite: غالباً مرن مع NULL حتى لو العمود أُنشئ NOT NULL في تعريفات قديمة
        } else {
            Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
                $table->foreignId('project_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('shareholder_ledger_entries', function (Blueprint $table): void {
            $table->dropForeign(['land_parcel_id']);
            $table->dropIndex(['land_parcel_id', 'type']);
            $table->dropColumn('land_parcel_id');
        });

        Schema::dropIfExists('land_parcel_shareholder');
    }
};
