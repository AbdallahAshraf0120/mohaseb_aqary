<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - أرقام إيصالات مسلسلة لكل مشروع (للتحصيلات ومقدمات البيع).
 * - بيانات الشيك على التحصيل + حالة الشيك.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_sequences')) {
            Schema::create('document_sequences', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->string('type', 30);
                $table->unsignedBigInteger('last_number')->default(0);
                $table->timestamps();
                $table->unique(['project_id', 'type']);
            });
        }

        Schema::table('revenues', function (Blueprint $table): void {
            if (! Schema::hasColumn('revenues', 'receipt_no')) {
                $table->unsignedBigInteger('receipt_no')->nullable()->after('id');
                $table->index(['project_id', 'receipt_no']);
            }
            if (! Schema::hasColumn('revenues', 'cheque_number')) {
                $table->string('cheque_number', 50)->nullable()->after('payment_method');
                $table->string('cheque_bank', 100)->nullable()->after('cheque_number');
                $table->date('cheque_due_date')->nullable()->after('cheque_bank');
                // in_hand: في الخزنة / deposited: اتودع البنك / cleared: اتصرف / bounced: ارتد
                $table->string('cheque_status', 20)->nullable()->after('cheque_due_date');
                $table->timestamp('cheque_status_at')->nullable()->after('cheque_status');
                $table->index(['project_id', 'cheque_status']);
            }
        });

        Schema::table('sales', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales', 'down_payment_receipt_no')) {
                $table->unsignedBigInteger('down_payment_receipt_no')->nullable()->after('down_payment');
            }
        });

        // ترقيم القديم حسب الترتيب الزمني داخل كل مشروع
        $counters = [];
        $events = [];
        foreach (DB::table('sales')->where('down_payment', '>', 0)->orderBy('created_at')->orderBy('id')->get(['id', 'project_id', 'created_at']) as $s) {
            $events[] = ['t' => (string) $s->created_at, 'kind' => 's', 'id' => $s->id, 'pid' => (int) $s->project_id];
        }
        foreach (DB::table('revenues')->orderBy('created_at')->orderBy('id')->get(['id', 'project_id', 'created_at']) as $r) {
            $events[] = ['t' => (string) $r->created_at, 'kind' => 'r', 'id' => $r->id, 'pid' => (int) $r->project_id];
        }
        usort($events, fn ($a, $b) => [$a['t'], $a['kind'], $a['id']] <=> [$b['t'], $b['kind'], $b['id']]);
        foreach ($events as $e) {
            $n = ($counters[$e['pid']] ?? 0) + 1;
            $counters[$e['pid']] = $n;
            if ($e['kind'] === 's') {
                DB::table('sales')->where('id', $e['id'])->update(['down_payment_receipt_no' => $n]);
            } else {
                DB::table('revenues')->where('id', $e['id'])->update(['receipt_no' => $n]);
            }
        }
        foreach ($counters as $pid => $last) {
            DB::table('document_sequences')->updateOrInsert(
                ['project_id' => $pid, 'type' => 'receipt'],
                ['last_number' => $last, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // الشيكات القديمة: نعتبرها اتصرفت (كانت داخلة الصندوق بالفعل)
        DB::table('revenues')->where('payment_method', 'check')->whereNull('cheque_status')
            ->update(['cheque_status' => 'cleared']);
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table): void {
            foreach (['receipt_no', 'cheque_number', 'cheque_bank', 'cheque_due_date', 'cheque_status', 'cheque_status_at'] as $c) {
                if (Schema::hasColumn('revenues', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
        Schema::table('sales', function (Blueprint $table): void {
            if (Schema::hasColumn('sales', 'down_payment_receipt_no')) {
                $table->dropColumn('down_payment_receipt_no');
            }
        });
        Schema::dropIfExists('document_sequences');
    }
};
