<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // تفريغ تواريخ الصرف لإعادة إدخالها يدويًا
        DB::table('expenses')->update(['spent_at' => null]);
    }

    public function down(): void
    {
        // لا يمكن استرجاع التواريخ المحذوفة؛ نرجع لتاريخ الإدخال كقيمة مؤقتة فقط
        DB::table('expenses')
            ->whereNull('spent_at')
            ->update(['spent_at' => DB::raw('DATE(created_at)')]);
    }
};
