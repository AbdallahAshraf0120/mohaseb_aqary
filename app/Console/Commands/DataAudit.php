<?php

namespace App\Console\Commands;

use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * فحص قراءة فقط لبيانات السيرفر: بيانات تجريبية، مبالغ غير منطقية، حسابات افتراضية، وعمليات معلّقة.
 * مش بيعدّل أي حاجة — بيطبع تقرير بس.
 *
 *   php artisan app:data-audit
 */
class DataAudit extends Command
{
    protected $signature = 'app:data-audit {--limit=15 : أقصى عدد صفوف يتعرض في كل جدول}';

    protected $description = 'Read-only audit of production data (demo data, typo-sized amounts, default accounts, stuck approvals)';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $problems = 0;

        $this->info('— فحص البيانات (قراءة فقط، مفيش أي تعديل) —');

        // 1) حسابات افتراضية
        $defaults = DB::table('users')
            ->where(fn ($q) => $q->where('email', 'like', '%@example.com')->orWhere('email', 'like', '%@test.local')->orWhere('email', 'like', '%@demo.%'))
            ->get(['id', 'name', 'email', 'role']);
        $problems += $this->section('حسابات مستخدمين افتراضية/تجريبية (امسحها أو غيّر إيميلها وكلمة سرها)', $defaults, $limit);

        // 2) إيميلات مكتوبة غلط
        $typos = DB::table('users')
            ->where(fn ($q) => $q->where('email', 'like', '%@gamil.com')->orWhere('email', 'like', '%@gmial.com')->orWhere('email', 'like', '%@gmail.co')->orWhere('email', 'like', '%@hotmial.com')->orWhere('email', 'like', '%@yahooo.com'))
            ->get(['id', 'name', 'email']);
        $problems += $this->section('إيميلات مستخدمين غالبًا فيها غلطة كتابة', $typos, $limit);

        // 3) عملاء تجريبيين من الـ seeder
        $demoClients = DB::table('clients')
            ->where('email', 'like', '%demo.example.com')
            ->select('project_id', DB::raw('count(*) as clients'))
            ->groupBy('project_id')
            ->get();
        $problems += $this->section('عملاء تجريبيين (من DatabaseSeeder) لكل مشروع', $demoClients, $limit);
        if ($demoClients->isNotEmpty()) {
            $pids = $demoClients->pluck('project_id')->all();
            $this->line('   المشاريع: '.DB::table('projects')->whereIn('id', $pids)->pluck('name', 'id')->map(fn ($n, $id) => "#$id $n")->implode('، '));
            $this->line('   حركات الصندوق في المشاريع دي: '.DB::table('treasury_transactions')->whereIn('project_id', $pids)->count());
        }

        // 4) مبالغ أكبر من الحد المنطقي
        $max = Money::MAX_AMOUNT;
        $huge = collect();
        foreach ([
            ['sales', 'sale_price'], ['sales', 'down_payment'], ['revenues', 'amount'], ['expenses', 'amount'],
            ['treasury_transactions', 'amount'], ['debts', 'total_amount'], ['land_parcels', 'purchase_price'],
        ] as [$table, $col]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $col)) {
                continue;
            }
            $cols = ['id', $col.' as amount'];
            if (Schema::hasColumn($table, 'project_id')) {
                $cols[] = 'project_id';
            }
            foreach (DB::table($table)->where($col, '>', $max)->get($cols) as $row) {
                $huge->push((object) ['table' => $table, 'column' => $col, 'id' => $row->id, 'project_id' => $row->project_id ?? '—', 'amount' => Money::display($row->amount)]);
            }
        }
        $problems += $this->section('مبالغ أكبر من '.Money::display($max).' ج.م (غالبًا أصفار زيادة)', $huge, $limit);

        // 5) عمليات معلّقة من كل الأنواع
        $pending = collect();
        foreach (['revenues', 'expenses', 'sales', 'debt_payments', 'land_parcel_payments'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'approval_status')) {
                $c = DB::table($table)->where('approval_status', 'pending')->count();
                if ($c > 0) {
                    $pending->push((object) ['type' => $table, 'count' => $c]);
                }
            }
        }
        foreach (DB::table('treasury_transactions')->where('approval_status', 'pending')
            ->select(DB::raw("coalesce(reference_type, 'manual') as ref"), DB::raw('count(*) as c'), DB::raw('sum(amount) as total'))
            ->groupBy('ref')->get() as $row) {
            $pending->push((object) ['type' => 'treasury: '.class_basename((string) $row->ref), 'count' => $row->c.' (إجمالي '.Money::display($row->total).')']);
        }
        $this->section('عمليات معلّقة (اعتمدها أو ارفضها من «طلبات الاعتماد»)', $pending, $limit, false);

        // 6) أسماء/أوصاف شكلها تجريبي
        $testy = collect();
        foreach ([['clients', 'name'], ['treasury_transactions', 'description'], ['projects', 'name']] as [$table, $col]) {
            foreach (DB::table($table)->where(fn ($q) => $q->whereIn($col, ['test', 'etst', 'tset', 'ffff', 'asdf', 'aaaa', 'xxx', 'من'])->orWhere($col, 'like', '%— ffff%')->orWhere($col, 'like', '%test%'))->limit($limit)->get(['id', $col.' as value']) as $row) {
                $testy->push((object) ['table' => $table, 'id' => $row->id, 'value' => mb_substr((string) $row->value, 0, 80)]);
            }
        }
        $problems += $this->section('سجلات اسمها أو وصفها شكله تجريبي (راجعها)', $testy, $limit);

        $this->newLine();
        $problems > 0
            ? $this->warn("لقيت {$problems} مجموعة محتاجة مراجعة. خد نسخة احتياطية قبل أي مسح.")
            : $this->info('مفيش مشاكل واضحة.');

        return self::SUCCESS;
    }

    private function section(string $title, $rows, int $limit, bool $isProblem = true): int
    {
        $this->newLine();
        $this->line('<options=bold>'.$title.'</>');
        if ($rows->isEmpty()) {
            $this->line('   ✓ لا يوجد');

            return 0;
        }
        $arr = $rows->take($limit)->map(fn ($r) => (array) $r)->values()->all();
        $this->table(array_keys($arr[0]), $arr);
        if ($rows->count() > $limit) {
            $this->line('   … و '.($rows->count() - $limit).' كمان');
        }

        return $isProblem ? 1 : 0;
    }
}
