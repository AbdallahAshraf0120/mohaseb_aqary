<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Sale;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class ContractWordDocumentService
{
    /**
     * ينسخ قالب المشروع (.docx) ويستبدل المتغيرات. في Word استخدم صيغة ‎${اسم_المتغير}‎ (مثل ‎${client_name}‎).
     *
     * @return string مسار ملف مؤقت جاهز للتنزيل
     */
    public function buildFilledDocument(Contract $contract): string
    {
        $contract->loadMissing(['client', 'property', 'sale', 'project']);
        $project = $contract->project;
        if (! $project || ! $project->contract_template_path) {
            throw new \InvalidArgumentException('missing_template');
        }

        $src = Storage::disk('local')->path($project->contract_template_path);
        if (! is_readable($src)) {
            throw new \InvalidArgumentException('missing_template');
        }

        $processor = new TemplateProcessor($src);
        $processor->setValues($this->placeholderValues($contract));

        $outPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .'contract_'.$contract->id.'_'.uniqid('', true).'.docx';

        $processor->saveAs($outPath);

        return $outPath;
    }

    /**
     * @return array<string, string>
     */
    private function placeholderValues(Contract $contract): array
    {
        $sale = $contract->sale;
        $client = $contract->client;
        $property = $contract->property;
        $project = $contract->project;

        $year = $contract->created_at?->format('Y') ?? now()->format('Y');
        $contractRef = 'CT-'.$year.'-'.str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT);

        $down = (float) ($sale?->down_payment ?? 0);
        $total = (float) $contract->total_price;
        $netAfterDown = max(0, round($total - $down, 2));

        return [
            'contract_number' => $contractRef,
            'project_name' => (string) ($project?->name ?? ''),
            'client_name' => (string) ($client?->name ?? ''),
            'client_phone' => (string) ($client?->phone ?? ''),
            'client_email' => (string) ($client?->email ?? ''),
            'client_national_id' => (string) ($client?->national_id ?? ''),
            'property_name' => (string) ($property?->name ?? ''),
            'sale_price' => $this->money($sale ? (float) $sale->sale_price : $total),
            'total_price' => $this->money($total),
            'down_payment' => $this->money($down),
            'net_after_down' => $this->money($netAfterDown),
            'paid_amount' => $this->money((float) $contract->paid_amount),
            'remaining_amount' => $this->money((float) $contract->remaining_amount),
            'start_date' => $contract->start_date?->format('Y-m-d') ?? '',
            'end_date' => $contract->end_date?->format('Y-m-d') ?? '',
            'sale_date' => $sale?->sale_date?->format('Y-m-d') ?? '',
            'payment_type' => $this->paymentTypeLabel($sale),
            'installment_months' => $sale && $sale->payment_type === 'installment'
                ? (string) (int) ($sale->installment_months ?? 0)
                : '',
            'broker_name' => (string) ($sale?->broker_name ?? ''),
            'floor_number' => $sale && (int) $sale->floor_number > 0 ? (string) (int) $sale->floor_number : '',
            'floor_label' => $this->floorLabel($sale),
            'apartment_model' => (string) ($sale?->apartment_model ?? ''),
            'sale_notes' => $this->oneLine($sale?->notes),
        ];
    }

    private function money(float $v): string
    {
        return number_format(round($v, 2), 2, '.', '');
    }

    private function paymentTypeLabel(?Sale $sale): string
    {
        if (! $sale) {
            return '';
        }

        return match ($sale->payment_type) {
            'installment' => 'تقسيط',
            'cash' => 'نقدي',
            default => (string) $sale->payment_type,
        };
    }

    private function floorLabel(?Sale $sale): string
    {
        if (! $sale || (int) $sale->floor_number < 1) {
            return '';
        }
        $base = 'الدور '.(int) $sale->floor_number;

        return $sale->is_mezzanine ? $base.' (ميزانين)' : $base;
    }

    private function oneLine(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return preg_replace('/\s+/u', ' ', trim($text)) ?? '';
    }
}
