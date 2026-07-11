<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->date('spent_at')->nullable()->after('description');
        });

        // السجلات القديمة: استخدم تاريخ التسجيل كتاريخ صرف
        DB::table('expenses')
            ->whereNull('spent_at')
            ->update(['spent_at' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn('spent_at');
        });
    }
};
