<?php

use App\Models\Shareholder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shareholder_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('shareholder_id')->constrained('shareholders')->cascadeOnDelete();
            $table->string('type'); // capital|withdrawal|distribution|settlement|adjustment
            $table->string('direction'); // credit|debit
            $table->decimal('amount', 14, 2);
            $table->date('entry_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['shareholder_id', 'entry_date']);
            $table->index(['project_id', 'type']);
        });

        // رصيد تاريخي: رأس المال المسجّل سابقاً بدون حركة صندوق
        $now = now();
        Shareholder::query()->withoutProjectScope()->orderBy('id')->chunkById(100, function ($shareholders) use ($now): void {
            $rows = [];
            foreach ($shareholders as $shareholder) {
                $amount = round((float) $shareholder->total_investment, 2);
                if ($amount <= 0) {
                    continue;
                }
                $rows[] = [
                    'project_id' => (int) $shareholder->project_id,
                    'shareholder_id' => (int) $shareholder->id,
                    'type' => 'capital',
                    'direction' => 'credit',
                    'amount' => $amount,
                    'entry_date' => optional($shareholder->created_at)->toDateString() ?? $now->toDateString(),
                    'notes' => 'رصيد افتتاحي من رأس المال المسجّل سابقاً (بدون صندوق)',
                    'created_by' => null,
                    'treasury_transaction_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows !== []) {
                DB::table('shareholder_ledger_entries')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shareholder_ledger_entries');
    }
};
