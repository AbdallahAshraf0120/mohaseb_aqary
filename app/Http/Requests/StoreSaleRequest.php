<?php

namespace App\Http\Requests;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
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
            'apartment_model' => ['required', 'string', 'max:255'],
            'sale_price' => ['required', 'numeric', 'min:1'],
            'payment_type' => ['required', 'in:cash,installment'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'installment_months' => ['nullable', 'integer', 'min:1', 'required_if:payment_type,installment'],
            'installment_schedule' => ['nullable', 'in:monthly,quarterly', 'required_if:payment_type,installment'],
            'installment_start_date' => ['nullable', 'date', 'required_if:payment_type,installment'],
            'sale_date' => ['required', 'date'],
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
            $hasGroundCommercial = (int) ($property->ground_floor_shops_count ?? 0) > 0;
            $registeredFloors = collect($property->registered_floors ?? [])
                ->map(static fn ($value) => (int) $value)
                ->filter(static fn (int $value) => $value >= 1)
                ->values();
            $mezzanineFloors = collect($property->mezzanine_floors ?? [])
                ->filter(static fn ($item) => is_array($item) && !empty($item['floor_number']))
                ->map(static fn (array $item) => (int) ($item['floor_number'] ?? 0))
                ->filter(static fn (int $value) => $value >= 1)
                ->values();
            $allowedFloors = $registeredFloors->merge($mezzanineFloors)->unique()->values();
            $maxFloor = $allowedFloors->max() ?: max(1, (int) ($property->floors_count ?? 1));

            if ($floor === 0 && ! $hasGroundCommercial) {
                $validator->errors()->add('floor_number', 'هذا العقار لا يحتوي وحدات بالدور الأرضي.');
            }

            if ($floor > 0 && $allowedFloors->isNotEmpty() && ! $allowedFloors->contains($floor)) {
                $validator->errors()->add('floor_number', 'هذا الدور غير مُسجل ضمن أدوار العقار.');
            }

            if ($floor > $maxFloor) {
                $validator->errors()->add('floor_number', 'رقم الدور غير متاح في هذا العقار.');
            }

            $availableModels = collect($property->apartment_models ?? [])->pluck('model_name')->filter()->values();
            if ($availableModels->isNotEmpty() && ! $availableModels->contains($this->input('apartment_model'))) {
                $validator->errors()->add('apartment_model', 'النموذج المختار غير موجود في هذا العقار.');
            }

            $salePrice = (float) $this->input('sale_price', 0);
            $downPayment = (float) $this->input('down_payment', 0);
            if ($downPayment > $salePrice) {
                $validator->errors()->add('down_payment', 'المقدم لا يمكن أن يكون أكبر من سعر البيع.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $isCash = $this->input('payment_type') === 'cash';
        $salePrice = (float) $this->input('sale_price', 0);
        $downPayment = $this->input('down_payment');
        $downPaymentValue = $downPayment === null || $downPayment === '' ? ($isCash ? $salePrice : 0) : (float) $downPayment;
        $installmentMonths = $isCash ? null : (int) $this->input('installment_months');
        $schedule = $isCash ? null : $this->input('installment_schedule', 'monthly');
        $intervalMonths = $schedule === 'quarterly' ? 3 : 1;
        $remaining = max(0, $salePrice - $downPaymentValue);
        $installmentsCount = ($installmentMonths && ! $isCash)
            ? max(1, (int) ceil($installmentMonths / $intervalMonths))
            : 0;
        $installmentAmount = $installmentsCount > 0 ? round($remaining / $installmentsCount, 2) : 0;

        $this->merge([
            'sale_date' => $this->input('sale_date', now()->toDateString()),
            'down_payment' => $downPaymentValue,
            'installment_schedule' => $schedule,
            'installment_plan' => $isCash ? null : [
                'schedule_type' => $schedule,
                'interval_months' => $intervalMonths,
                'installments_count' => $installmentsCount,
                'remaining_amount' => $remaining,
                'installment_amount' => $installmentAmount,
                // Keep backward compatibility for any old consumers.
                'monthly_installment' => $installmentAmount,
            ],
        ]);
    }
}
