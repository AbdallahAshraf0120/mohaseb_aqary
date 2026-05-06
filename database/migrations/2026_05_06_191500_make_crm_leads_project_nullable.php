<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('crm_leads')) {
            return;
        }

        Schema::table('crm_leads', function (Blueprint $table): void {
            // drop FK first (works on MySQL with conventional name)
            try {
                $table->dropForeign(['project_id']);
            } catch (\Throwable) {
                // ignore if already dropped or name differs
            }
        });

        // Make column nullable without requiring doctrine/dbal.
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE crm_leads MODIFY project_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE crm_leads ALTER COLUMN project_id DROP NOT NULL');
        } else {
            // sqlite: keep as-is (dev), controller no longer depends on it.
        }

        Schema::table('crm_leads', function (Blueprint $table): void {
            // Re-create FK but allow nulls
            try {
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            } catch (\Throwable) {
                // ignore
            }
        });
    }

    public function down(): void
    {
        // no-op (safe)
    }
};

