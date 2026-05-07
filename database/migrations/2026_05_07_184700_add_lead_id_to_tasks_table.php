<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('crm_lead_id')
                ->nullable()
                ->after('assigned_to')
                ->constrained('crm_leads')
                ->nullOnDelete();

            $table->index('crm_lead_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropForeign(['crm_lead_id']);
            $table->dropColumn('crm_lead_id');
        });
    }
};

