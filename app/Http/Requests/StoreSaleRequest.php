<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', 'exists:properties,id'],
            'floor_number' => ['required', 'integer', 'min:0'],
            'is_mezzanine' => ['sometimes', 'boolean'],
            'apartment_model' => ['required', 'string', 'max:255'],
            'sale_price' => ['required', 'numeric', 'min:1'],
            'payment_type' => ['required', 'in:cash,installment'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'installment_months' => ['nullable', 'integer', 'min:1', 'required_if:payment_type,installment'],
            'installment_schedule' => ['nullable', 'in:monthly,quarterly,semiannual', 'required_if:payment_type,installment'],
            'installment_start_date' => ['nullable', 'date', 'required_if:payment_type,installment'],
            'secondary_payments' => ['nullable', 'array', 'max:30'],
            'secondary_payments.*.label' => ['nullable', 'string', 'max:255'],
            'secondary_payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'secondary_payments.*.due_date' => ['required', 'date'],
            // Built in prepareForValidation(); must be in rules so validated() includes it on store/update.
            'installment_plan' => ['nullable', 'array', 'required_if:payment_type,installment'],
            'sale_date' => ['required', 'date'],
            'broker_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:30'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_national_id' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $property = Property::query()->find((int) $this->input('property_id'));
            if (! $property) {
                return;
            }

            $floor = (int) $this->input('floor_number');
            $isMezzanine = $this->boolean('is_mezzanine');
            $hasGroundCommercial = (int) ($property->ground_floor_shops_count ?? 0) > 0;
            $registeredFloors = collect($property->registered_floors ?? [])
                ->map(static fn ($value) => (int) $value)
                ->filter(static fn (int $value) => $value >= 1)
                ->values();
            $mezzanineFloorNums = collect($property->mezzanine_floors ?? [])
                ->filter(static fn ($item) => is_array($item) && ! empty($item['floor_number']))
                ->map(static fn (array $item) => (int) ($item['floor_number'] ?? 0))
                ->filter(static fn (int $value) => $value >= 1)
                ->unique()
                ->values();
            $residentialFloors = $registeredFloors->isNotEmpty()
                ? $registeredFloors
                : collect(range(1, max(1, (int) ($property->floors_count ?? 1))));
            $maxFloor = max(
                1,
                (int) ($property->floors_count ?? 1),
                (int) ($residentialFloors->max() ?? 0),
                (int) ($mezzanineFloorNums->max() ?? 0),
            );

            if ($floor === 0 && ! $hasGroundCommercial) {
                $validator->errors()->add('floor_number', 'هذا العقار لا يحتوي وحدات بالدور الأرضي.');
            }

            if ($floor === 0 && $isMezzanine) {
                $validator->errors()->add('floor_number', 'الميزان لا ينطبق على الدور الأرضي التجاري.');
            }

            if ($floor > 0 && $isMezzanine) {
                if ($mezzanineFloorNums->isEmpty() || ! $mezzanineFloorNums->contains($floor)) {
                    $validator->errors()->add('floor_number', 'هذا الدور غير مُعرَّف كميزان في بيانات العقار.');
                }
            }

            if ($floor > 0 && ! $isMezzanine) {
                if ($residentialFloors->isNotEmpty() && ! $residentialFloors->contains($floor)) {
                    $validator->errors()->add('floor_number', 'هذا الدور غير مُسجل ضمن أدوار العقار (السكنية).');
                }
            }

            if ($floor > $maxFloor) {
                $validator->errors()->add('floor_number', 'رقم الدور غير متاح في هذا العقار.');
            }

            $availableModels = collect($property->apartment_models ?? [])->pluck('model_name')->filter()->values();
            if ($availableModels->isNotEmpty() && ! $availableModels->contains($this->input('apartment_model'))) {
                $validator->errors()->add('apartment_model', 'النموذج المختار غير موجود في هذا العقار.');
            }

            if ($validator->errors()->has('floor_number') || $validator->errors()->has('apartment_model')) {
                return;
            }

            $modelName = (string) $this->input('apartment_model');
            $ignoreSaleId = $this->saleIdToIgnoreForDuplicateUnitCheck();
            $duplicateQuery = Sale::query()->withoutGlobalScope('project')
                ->where('property_id', (int) $property->id)
                ->where('floor_number', $floor)
                ->where('is_mezzanine', $isMezzanine)
                ->where('apartment_model', $modelName);
            if ($ignoreSaleId !== null) {
                $duplicateQuery->whereKeyNot($ignoreSaleId);
            }
            if ($duplicateQuery->exists()) {
                $validator->errors()->add(
                    'apartment_model',
                    'لا يمكن إتمام البيعة: هذه الوحدة (نفس العقار والدور والنموذج) مبيعة بالفعل.'
                );
            }

            $salePrice = (float) $this->input('sale_price', 0);
            $downPayment = (float) $this->input('down_payment', 0);
            if ($downPayment > $salePrice) {
                $validator->errors()->add('down_payment', 'المقدم لا يمكن أن يكون أكبر من سعر البيع.');
            }

            if ($this->input('payment_type') === 'installment' && is_array($this->input('installment_plan'))) {
                $plan = $this->input('installment_plan');
                $remaining = (float) ($plan['remaining_amount'] ?? 0);
                $secondaryTotal = (float) ($plan['secondary_payments_total'] ?? 0);
                if ($secondaryTotal > $remaining + 0.01) {
                    $validator->errors()->add(
                        'secondary_payments',
                        'إجمالي الدفعات الثانوية لا يمكن أن يتجاوز المتبقي بعد المقدم ('.number_format($remaining, 2).' ج.م).'
                    );
                }
                $cnt = (int) ($plan['installments_count'] ?? 0);
                $base = (float) ($plan['installment_base_for_schedule'] ?? 0);
                if ($cnt > 0 && $base < 0.01) {
                    $validator->errors()->add(
                        'secondary_payments',
                        'لا يتبقى مبلغ كافٍ لأقساط التقسيط المنتظمة بعد الدفعات الثانوية؛ قلّل الدفعات الثانوية أو غيّر المقدم/السعر.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('apartment_model') && is_string($this->input('apartment_model'))) {
            $this->merge(['apartment_model' => trim($this->input('apartment_model'))]);
        }

        if ($this->has('secondary_payments') && is_array($this->input('secondary_payments'))) {
            $this->merge([
                'secondary_payments' => self::normalizeSecondaryPaymentsInput($this->input('secondary_payments')),
            ]);
        }

        $isCash = $this->input('payment_type') === 'cash';
        $salePrice = (float) $this->input('sale_price', 0);
        $downPayment = $this->input('down_payment');
        $downPaymentValue = $downPayment === null || $downPayment === '' ? ($isCash ? $salePrice : 0) : (float) $downPayment;
        $installmentMonths = $isCash ? null : (int) $this->input('installment_months');
        $schedule = $isCash ? null : $this->input('installment_schedule', 'monthly');
        $intervalMonths = match ($schedule) {
            'quarterly' => 3,
            'semiannual' => 6,
            default => 1,
        };
        $remaining = max(0, round($salePrice - $downPaymentValue, 2));

        $secondaryRows = $isCash
            ? []
            : self::normalizeSecondaryPaymentsInput($this->input('secondary_payments', []));
        $secondaryTotal = round((float) collect($secondaryRows)->sum(static fn (array $r) => (float) $r['amount']), 2);
        $installmentBaseForSchedule = max(0, round($remaining - $secondaryTotal, 2));

        $installmentsCount = ($installmentMonths && ! $isCash)
            ? max(1, (int) ceil($installmentMonths / $intervalMonths))
            : 0;
        $installmentAmount = ($installmentsCount > 0 && $installmentBaseForSchedule > 0)
            ? round($installmentBaseForSchedule / $installmentsCount, 2)
            : 0;

        $this->merge([
            'sale_date' => $this->input('sale_date', now()->toDateString()),
            'is_mezzanine' => $this->boolean('is_mezzanine'),
            'down_payment' => $downPaymentValue,
            'installment_schedule' => $schedule,
            'installment_plan' => $isCash ? null : [
                'schedule_type' => $schedule,
                'interval_months' => $intervalMonths,
                'installments_count' => $installmentsCount,
                'remaining_amount' => $remaining,
                'secondary_payments' => $secondaryRows,
                'secondary_payments_total' => $secondaryTotal,
                'installment_base_for_schedule' => $installmentBaseForSchedule,
                'installment_amount' => $installmentAmount,
                // Keep backward compatibility for any old consumers.
                'monthly_installment' => $installmentAmount,
            ],
        ]);
    }

    /**
     * On update, the route may expose the bound Sale or only its key (string) depending on pipeline timing.
     */
    protected function saleIdToIgnoreForDuplicateUnitCheck(): ?int
    {
        $sale = $this->route('sale');

        if ($sale instanceof Sale) {
            $key = $sale->getKey();

            return is_numeric($key) ? (int) $key : null;
        }

        if (is_int($sale) || (is_string($sale) && ctype_digit($sale))) {
            return (int) $sale;
        }

        return null;
    }

    /**
     * @return list<array{label: string, amount: float, due_date: string}>
     */
    private static function normalizeSecondaryPaymentsInput(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return Collection::make($raw)
            ->filter(static fn ($row) => is_array($row))
            ->map(static function (array $row): ?array {
                $due = $row['due_date'] ?? null;
                $amount = $row['amount'] ?? null;
                if ($due === null || $due === '' || $amount === null || $amount === '') {
                    return null;
                }
                $amt = round((float) $amount, 2);
                if ($amt <= 0) {
                    return null;
                }

                return [
                    'label' => trim((string) ($row['label'] ?? '')) ?: 'دفعة ثانوية',
                    'amount' => $amt,
                    'due_date' => (string) $due,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
