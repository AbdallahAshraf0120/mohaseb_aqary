<?php

namespace App\Services;

use App\Http\Controllers\SaleController;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Client;
use App\Models\Property;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * استيراد البيانات القديمة من Excel: العملاء + المبيعات (بعقودها وجدول أقساطها والمحصّل قبل كده).
 * كل صف بيعدّي على نفس قواعد فورم البيع (StoreSaleRequest) عشان مفيش بيانات غلط تدخل.
 */
class SalesImportService
{
    /** عمود ⇐ أسماء مقبولة في أول صف */
    public const SALE_COLUMNS = [
        'client_name' => ['اسم العميل', 'العميل', 'client name', 'name'],
        'client_phone' => ['الموبايل', 'الهاتف', 'رقم الموبايل', 'phone', 'mobile'],
        'client_national_id' => ['الرقم القومي', 'national id'],
        'client_email' => ['البريد', 'الإيميل', 'email'],
        'property' => ['العقار', 'العمارة', 'property', 'building'],
        'floor_number' => ['الدور', 'floor'],
        'unit_number' => ['رقم الشقة', 'رقم الوحدة', 'الشقة', 'unit'],
        'apartment_model' => ['النموذج', 'model'],
        'sale_price' => ['سعر البيع', 'السعر', 'price'],
        'down_payment' => ['المقدم', 'down payment'],
        'sale_date' => ['تاريخ البيع', 'تاريخ التعاقد', 'sale date'],
        'payment_type' => ['نظام الدفع', 'طريقة الدفع', 'payment type'],
        'installment_months' => ['مدة التقسيط بالشهور', 'مدة التقسيط', 'months'],
        'installment_schedule' => ['القسط كل', 'دورية القسط', 'schedule'],
        'installment_start_date' => ['تاريخ أول قسط', 'first installment'],
        'collected' => ['المحصل من الأقساط', 'المحصّل من الأقساط', 'المحصل', 'collected'],
        'broker_name' => ['البروكر', 'المسوق', 'broker'],
        'notes' => ['ملاحظات', 'notes'],
    ];

    public function template(): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('المبيعات');
        $sheet->setRightToLeft(true);
        $headers = array_map(fn (array $names) => $names[0], self::SALE_COLUMNS);
        $sheet->fromArray(array_values($headers), null, 'A1');
        $sheet->fromArray([
            ['محمد أحمد علي', '01001234567', '29001011234567', '', 'عمارة 1', 3, 2, 'A', 1200000, 200000, '2025-01-15', 'تقسيط', 24, 'شهري', '2025-02-15', 300000, 'أحمد', ''],
            ['سارة محمود', '01112223333', '', '', 'عمارة 1', 0, 1, 'محل', 900000, 900000, '2025-03-01', 'كاش', '', '', '', '', 'مكتب', 'محل أرضي'],
        ], null, 'A2', true);
        $last = $sheet->getHighestColumn();
        $sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true);
        $sheet->getStyle('A1:'.$last.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
        foreach (range('A', $last) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        $help = $book->createSheet();
        $help->setTitle('تعليمات');
        $help->setRightToLeft(true);
        $help->fromArray([
            ['التعليمات'],
            ['- امسح صفوف المثال وحط بياناتك من الصف 2.'],
            ['- العقار لازم يكون متسجل في البرنامج قبل الاستيراد بنفس الاسم.'],
            ['- الدور: 0 للأرضي (محلات). رقم الشقة من 1 لعدد شقق الدور.'],
            ['- نظام الدفع: كاش أو تقسيط. القسط كل: شهري / 3 شهور / 6 شهور.'],
            ['- المحصّل من الأقساط: إجمالي اللي العميل دفعه بعد المقدم لحد النهارده (اختياري).'],
            ['- التواريخ بصيغة 2025-01-31 أو تاريخ Excel عادي.'],
            ['- العميل اللي ليه نفس الرقم القومي أو نفس الموبايل والاسم بيتسجل مرة واحدة.'],
        ]);
        $help->getColumnDimension('A')->setWidth(90);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readRows(string $path): array
    {
        $book = IOFactory::load($path);
        $sheet = $book->getSheet(0);
        $data = $sheet->toArray(null, true, false, false);
        if (count($data) < 2) {
            return [];
        }
        $map = [];
        foreach ($data[0] as $i => $header) {
            $h = mb_strtolower(trim((string) $header));
            foreach (self::SALE_COLUMNS as $key => $names) {
                if (in_array($h, array_map('mb_strtolower', $names), true)) {
                    $map[$i] = $key;
                }
            }
        }
        $rows = [];
        foreach (array_slice($data, 1) as $n => $raw) {
            $row = ['_line' => $n + 2];
            $empty = true;
            foreach ($map as $i => $key) {
                $v = $raw[$i] ?? null;
                if (is_string($v)) {
                    $v = trim($v);
                }
                if ($v !== null && $v !== '') {
                    $empty = false;
                }
                $row[$key] = $v;
            }
            if (! $empty) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return list<string> الأعمدة الإجبارية الناقصة من الملف */
    public function missingColumns(array $rows): array
    {
        $first = $rows[0] ?? [];
        $required = ['client_name', 'client_phone', 'property', 'floor_number', 'unit_number', 'sale_price', 'payment_type'];

        return array_values(array_map(fn ($k) => self::SALE_COLUMNS[$k][0], array_filter($required, fn ($k) => ! array_key_exists($k, $first))));
    }

    /**
     * يجهّز صف لبيانات فورم البيع.
     *
     * @return array{0: array<string, mixed>|null, 1: list<string>}
     */
    public function toSaleInput(array $row, array $propertiesByName): array
    {
        $errors = [];
        $propName = trim((string) ($row['property'] ?? ''));
        $prop = $propertiesByName[$this->norm($propName)] ?? null;
        if (! $prop) {
            $errors[] = 'العقار «'.$propName.'» مش متسجل في المشروع.';
        }
        $type = $this->norm((string) ($row['payment_type'] ?? ''));
        $paymentType = match (true) {
            in_array($type, ['كاش', 'نقدي', 'نقدا', 'cash'], true) => 'cash',
            in_array($type, ['تقسيط', 'اقساط', 'installment', 'installments'], true) => 'installment',
            default => null,
        };
        if ($paymentType === null) {
            $errors[] = 'نظام الدفع لازم يكون «كاش» أو «تقسيط».';
        }
        $sched = $this->norm((string) ($row['installment_schedule'] ?? ''));
        $schedule = match (true) {
            $sched === '' || str_contains($sched, 'شهري') || $sched === '1' || $sched === 'monthly' => 'monthly',
            str_contains($sched, '3') || str_contains($sched, 'ربع') || $sched === 'quarterly' => 'quarterly',
            str_contains($sched, '6') || str_contains($sched, 'نصف') || $sched === 'semiannual' => 'semiannual',
            default => 'monthly',
        };
        $price = $this->num($row['sale_price'] ?? null);
        $down = $this->num($row['down_payment'] ?? null);
        $floor = $this->num($row['floor_number'] ?? null);
        $model = trim((string) ($row['apartment_model'] ?? ''));
        if ($model === '' && $prop) {
            $model = (string) (collect($prop->apartment_models ?? [])->pluck('model_name')->filter()->first() ?? '-');
        }

        $input = [
            'property_id' => $prop?->id,
            'floor_number' => $floor === null ? null : (int) $floor,
            'unit_number' => $this->num($row['unit_number'] ?? null) === null ? null : (int) $this->num($row['unit_number']),
            'apartment_model' => $model ?: '-',
            'sale_price' => $price,
            'payment_type' => $paymentType,
            'down_payment' => $down ?? ($paymentType === 'cash' ? $price : 0),
            'sale_date' => $this->date($row['sale_date'] ?? null) ?? now()->toDateString(),
            'broker_name' => trim((string) ($row['broker_name'] ?? '')) ?: 'استيراد',
            'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
            'client_name' => trim((string) ($row['client_name'] ?? '')),
            'client_phone' => trim((string) ($row['client_phone'] ?? '')),
            'client_national_id' => trim((string) ($row['client_national_id'] ?? '')) ?: null,
            'client_email' => trim((string) ($row['client_email'] ?? '')) ?: null,
        ];
        if ($paymentType === 'installment') {
            $input['installment_months'] = (int) ($this->num($row['installment_months'] ?? null) ?? 0) ?: null;
            $input['installment_schedule'] = $schedule;
            $input['installment_start_date'] = $this->date($row['installment_start_date'] ?? null);
        }
        $collected = (float) ($this->num($row['collected'] ?? null) ?? 0);
        if ($collected < 0) {
            $errors[] = 'المحصّل مينفعش يكون بالسالب.';
        }
        if ($price !== null && $collected + (float) $input['down_payment'] - $price > 0.01) {
            $errors[] = 'المقدم + المحصّل أكبر من سعر البيع.';
        }
        $input['_collected'] = $collected;

        return [$errors === [] ? $input : $input + ['_errors' => true], $errors];
    }

    /**
     * يتحقق من كل الصفوف (ومن تكرار نفس الوحدة جوه الملف)، ولو $commit يستورد الصفوف السليمة.
     *
     * @return array{rows: list<array<string, mixed>>, ok: int, failed: int, imported: int, cash_in: float}
     */
    public function process(array $rows, User $user, bool $commit, bool $keepCashbox = true): array
    {
        $propertiesByName = Property::query()->get(['id', 'name', 'apartment_models'])
            ->keyBy(fn (Property $p) => $this->norm((string) $p->name))->all();
        $seenUnits = [];
        $out = [];
        $ok = $failed = $imported = 0;
        $cashIn = 0.0;

        foreach ($rows as $row) {
            [$input, $errors] = $this->toSaleInput($row, $propertiesByName);
            $unitKey = ($input['property_id'] ?? '').'|'.($input['floor_number'] ?? '').'|'.($input['unit_number'] ?? '');
            if (isset($seenUnits[$unitKey]) && ($input['property_id'] ?? null)) {
                $errors[] = 'نفس الوحدة متكررة في الملف (سطر '.$seenUnits[$unitKey].').';
            }
            $seenUnits[$unitKey] = $row['_line'];

            $request = null;
            if ($errors === []) {
                [$request, $validationErrors] = $this->validate($input, $user);
                $errors = array_merge($errors, $validationErrors);
            }

            $saleId = null;
            if ($errors === [] && $commit && $request) {
                try {
                    $saleId = DB::transaction(fn () => $this->importOne($request, (float) $input['_collected'], $user, $keepCashbox));
                    $imported++;
                    $cashIn += (float) $request->validated()['down_payment'] + (float) $input['_collected'];
                } catch (\Throwable $e) {
                    $errors[] = 'تعذّر الحفظ: '.mb_substr($e->getMessage(), 0, 150);
                }
            }

            $errors === [] ? $ok++ : $failed++;
            $out[] = [
                'line' => $row['_line'],
                'client' => $input['client_name'] ?? '',
                'unit' => trim(($row['property'] ?? '').' — دور '.($input['floor_number'] ?? '?').' شقة '.($input['unit_number'] ?? '?')),
                'price' => (float) ($input['sale_price'] ?? 0),
                'type' => $input['payment_type'] ?? null,
                'collected' => (float) ($input['_collected'] ?? 0),
                'errors' => $errors,
                'sale_id' => $saleId,
            ];
        }

        return ['rows' => $out, 'ok' => $ok, 'failed' => $failed, 'imported' => $imported, 'cash_in' => $cashIn];
    }

    /** @return array{0: ?StoreSaleRequest, 1: list<string>} */
    private function validate(array $input, User $user): array
    {
        $data = array_filter($input, fn ($k) => ! str_starts_with((string) $k, '_'), ARRAY_FILTER_USE_KEY);
        $request = StoreSaleRequest::createFrom(Request::create('/import', 'POST', $data));
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => $user);
        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            return [null, array_values(array_unique($e->validator->errors()->all()))];
        }

        return [$request, []];
    }

    private function importOne(StoreSaleRequest $request, float $collected, User $user, bool $keepCashbox): int
    {
        app(SaleController::class)->store($request);
        $sale = Sale::query()->latest('id')->firstOrFail();
        $sale->load('contract');

        if ($collected > 0.00001 && $sale->contract) {
            $revenue = Revenue::query()->create([
                'contract_id' => $sale->contract->id,
                'sale_id' => $sale->id,
                'client_id' => $sale->client_id,
                'amount' => $collected,
                'category' => 'تحصيلات سابقة (استيراد)',
                'paid_at' => $sale->sale_date,
                'payment_method' => 'cash',
                'notes' => 'اتسجلت من ملف الاستيراد',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $user->id,
            ]);
            app(CashboxLedgerService::class)->syncFromRevenue($revenue);
            app(ContractBalanceService::class)->recalculate((int) $sale->contract->id);
        }

        if ($keepCashbox) {
            $in = (float) $sale->cashboxDownPaymentAmount() + $collected;
            if ($in > 0.00001) {
                // الفلوس دي اتحصّلت زمان ومش موجودة في الصندوق دلوقتي: تسوية بنفس القيمة (مش بتدخل في حساب الربح)
                TreasuryTransaction::query()->create([
                    'project_id' => $sale->project_id,
                    'type' => 'expense',
                    'amount' => $in,
                    'reference_type' => 'import_adjustment',
                    'reference_id' => $sale->id,
                    'description' => 'تسوية رصيد — بيانات قديمة مستوردة (بيعة SL-'.$sale->id.')',
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                ]);
            }
        }

        return (int) $sale->id;
    }

    private function norm(string $s): string
    {
        return ClientDirectory::normalizeName($s);
    }

    private function num(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $s = strtr((string) $v, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', '٬' => '', ',' => '']);
        $s = preg_replace('/[^\d.\-]/', '', $s) ?? '';

        return is_numeric($s) ? (float) $s : null;
    }

    private function date(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v) && (float) $v > 20000 && (float) $v < 80000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->toDateString();
        }
        try {
            return Carbon::parse(str_replace('/', '-', (string) $v))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
