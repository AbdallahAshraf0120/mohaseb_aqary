<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\TreasuryTransaction;
use App\Support\ListingFilters;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ProjectReportsExcelExporter
{
    /**
     * @param  array<string, float|int|string>  $periodStats
     * @param  array<string, float>  $allTime
     */
    public function __construct(
        private readonly Project $project,
        private readonly string $currencyLabel,
        private readonly string $fromStr,
        private readonly string $toStr,
        private readonly ListingFilters $filters,
        private readonly array $periodStats,
        private readonly array $allTime,
        private readonly float $contractsRemaining,
        private readonly int $contractsOpenCount,
        private readonly Builder $revenuesQuery,
        private readonly Builder $expensesQuery,
        private readonly Builder $salesQuery,
        private readonly Builder $treasuryInQuery,
        private readonly Builder $treasuryOutQuery,
        private readonly Builder $contractsQuery,
    ) {}

    public function downloadFilename(): string
    {
        return 'report-'.$this->project->id.'-'.$this->fromStr.'-'.$this->toStr.'.xlsx';
    }

    public function stream(): void
    {
        $spreadsheet = $this->buildSpreadsheet();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($writer, $spreadsheet);
    }

    private function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Mohaseb Aqary')
            ->setTitle('تقرير '.$this->project->name);

        $summary = $spreadsheet->getActiveSheet();
        $this->fillSummarySheet($summary);
        $summary->setTitle('ملخص');

        $this->fillDataSheet($spreadsheet->createSheet(), 'تحصيلات', $this->buildRevenueRows());
        $this->fillDataSheet($spreadsheet->createSheet(), 'مصروفات', $this->buildExpenseRows());
        $this->fillDataSheet($spreadsheet->createSheet(), 'مبيعات', $this->buildSaleRows());
        $this->fillDataSheet($spreadsheet->createSheet(), 'صندوق وارد', $this->buildTreasuryRows(clone $this->treasuryInQuery));
        $this->fillDataSheet($spreadsheet->createSheet(), 'صندوق صادر', $this->buildTreasuryRows(clone $this->treasuryOutQuery));
        $this->fillDataSheet($spreadsheet->createSheet(), 'عقود', $this->buildContractRows());

        $spreadsheet->setActiveSheetIndex(0);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->setRightToLeft(true);
            $hi = Coordinate::columnIndexFromString($sheet->getHighestColumn());
            for ($i = 1; $i <= $hi; $i++) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }

    private function fillSummarySheet(Worksheet $sheet): void
    {
        $row = 1;
        $sheet->setCellValue([1, $row], 'Mohaseb Aqary — تقرير مشروع');
        $sheet->setCellValue([2, $row], $this->project->name);
        $row++;
        $sheet->setCellValue([1, $row], 'من');
        $sheet->setCellValue([2, $row], $this->fromStr);
        $row++;
        $sheet->setCellValue([1, $row], 'إلى');
        $sheet->setCellValue([2, $row], $this->toStr);
        $row++;
        $sheet->setCellValue([1, $row], 'البحث');
        $sheet->setCellValue([2, $row], $this->filters->q !== '' ? $this->filters->q : '—');
        $row++;
        $sheet->setCellValue([1, $row], 'العملة');
        $sheet->setCellValue([2, $row], $this->currencyLabel);
        $row += 2;

        $sheet->setCellValue([1, $row], 'ملخص الفترة');
        $sheet->getStyle([1, $row])->getFont()->setBold(true);
        $row++;

        $pairs = [
            ['تحصيلات الفترة (إجمالي)', $this->periodStats['revenues_sum']],
            ['عدد إيصالات التحصيل', $this->periodStats['revenues_count']],
            ['مصروفات الفترة (إجمالي)', $this->periodStats['expenses_sum']],
            ['عدد سجلات المصروفات', $this->periodStats['expenses_count']],
            ['صافي (تحصيل − مصروف)', $this->periodStats['net_revenue_expense']],
            ['مبيعات الفترة (إجمالي)', $this->periodStats['sales_sum']],
            ['مجموع المقدمات', $this->periodStats['sales_down']],
            ['عدد المبيعات', $this->periodStats['sales_count']],
            ['صندوق الفترة — وارد', $this->periodStats['treasury_in']],
            ['صندوق الفترة — صادر', $this->periodStats['treasury_out']],
            ['صندوق الفترة — صافي', $this->periodStats['net_treasury']],
        ];

        foreach ($pairs as [$label, $value]) {
            $sheet->setCellValue([1, $row], $label);
            $sheet->setCellValue([2, $row], is_numeric($value) ? (float) $value : $value);
            $row++;
        }

        $row++;
        $sheet->setCellValue([1, $row], 'إجماليات المشروع (كل الفترات)');
        $sheet->getStyle([1, $row])->getFont()->setBold(true);
        $row++;

        $allPairs = [
            ['تحصيلات متراكمة', $this->allTime['revenues_sum']],
            ['مصروفات متراكمة', $this->allTime['expenses_sum']],
            ['وارد الصندوق اليدوي', $this->allTime['treasury_in']],
            ['صادر الصندوق اليدوي', $this->allTime['treasury_out']],
            ['صافي الصندوق', $this->allTime['treasury_net']],
            ['المتبقي الحالي على العقود', $this->contractsRemaining],
            ['عدد العقود ذات متبقٍ', $this->contractsOpenCount],
        ];

        foreach ($allPairs as [$label, $value]) {
            $sheet->setCellValue([1, $row], $label);
            $sheet->setCellValue([2, $row], is_numeric($value) ? (float) $value : $value);
            $row++;
        }
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function fillDataSheet(Worksheet $sheet, string $title, array $rows): void
    {
        $sheet->setTitle(mb_substr($title, 0, 31));
        if ($rows === []) {
            $sheet->setCellValue([1, 1], 'لا توجد بيانات');

            return;
        }

        $headers = $rows[0];
        $dataRows = array_slice($rows, 1);
        $colCount = count($headers);
        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 1], $header);
        }
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $sheet->getStyle('A1:'.$lastCol.'1')->getFont()->setBold(true);

        $r = 2;
        foreach ($dataRows as $line) {
            foreach (array_values($line) as $i => $cell) {
                $sheet->setCellValue([$i + 1, $r], $cell);
            }
            $r++;
        }
    }

    /**
     * @return list<list<mixed>>
     */
    private function buildRevenueRows(): array
    {
        $headers = ['المعرّف', 'تاريخ التحصيل', 'المبلغ', 'العميل', 'التصنيف', 'طريقة الدفع', 'المصدر', 'ملاحظات'];
        $rows = [$headers];
        /** @var Revenue $rev */
        foreach ((clone $this->revenuesQuery)->with(['client:id,name'])->orderByDesc('paid_at')->orderByDesc('id')->cursor() as $rev) {
            $rows[] = [
                $rev->id,
                optional($rev->paid_at)->format('Y-m-d'),
                (float) $rev->amount,
                $rev->client?->name,
                (string) ($rev->category ?? ''),
                (string) ($rev->payment_method ?? ''),
                (string) ($rev->source ?? ''),
                (string) ($rev->notes ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function buildExpenseRows(): array
    {
        $headers = ['المعرّف', 'تاريخ التسجيل', 'المبلغ', 'التصنيف', 'الوصف'];
        $rows = [$headers];
        /** @var Expense $ex */
        foreach ((clone $this->expensesQuery)->orderByDesc('id')->cursor() as $ex) {
            $rows[] = [
                $ex->id,
                $ex->created_at?->format('Y-m-d H:i'),
                (float) $ex->amount,
                (string) ($ex->category ?? ''),
                (string) ($ex->description ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function buildSaleRows(): array
    {
        $headers = ['المعرّف', 'تاريخ البيع', 'العميل', 'العقار', 'سعر البيع', 'المقدم', 'نوع الدفع', 'وسيط', 'ملاحظات'];
        $rows = [$headers];
        /** @var Sale $sale */
        foreach ((clone $this->salesQuery)->with(['client:id,name', 'property:id,name'])->orderByDesc('sale_date')->orderByDesc('id')->cursor() as $sale) {
            $rows[] = [
                $sale->id,
                optional($sale->sale_date)->format('Y-m-d'),
                $sale->client?->name,
                $sale->property?->name,
                (float) $sale->sale_price,
                (float) $sale->down_payment,
                (string) ($sale->payment_type ?? ''),
                (string) ($sale->broker_name ?? ''),
                (string) ($sale->notes ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function buildTreasuryRows(Builder $query): array
    {
        $headers = ['المعرّف', 'التاريخ', 'المبلغ', 'الوصف'];
        $rows = [$headers];
        /** @var TreasuryTransaction $t */
        foreach ((clone $query)->orderByDesc('created_at')->orderByDesc('id')->cursor() as $t) {
            $rows[] = [
                $t->id,
                $t->created_at?->format('Y-m-d H:i'),
                (float) $t->amount,
                (string) ($t->description ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<mixed>>
     */
    private function buildContractRows(): array
    {
        $headers = ['المعرّف', 'العميل', 'العقار', 'إجمالي السعر', 'المدفوع', 'المتبقي', 'بداية العقد', 'نهاية العقد'];
        $rows = [$headers];
        /** @var Contract $c */
        foreach ((clone $this->contractsQuery)->with(['client:id,name', 'property:id,name'])->orderBy('id')->cursor() as $c) {
            $rows[] = [
                $c->id,
                $c->client?->name,
                $c->property?->name,
                (float) $c->total_price,
                (float) $c->paid_amount,
                (float) $c->remaining_amount,
                optional($c->start_date)->format('Y-m-d'),
                optional($c->end_date)->format('Y-m-d'),
            ];
        }

        return $rows;
    }
}
