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
            'floor_number' => ['required', 'integer', 'min:1'],
            'apartment_model' => ['required', 'string', 'max:255'],
            'sale_price' => ['required', 'numeric', 'min:1'],
            'payment_type' => ['required', 'in:cash,installment'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'installment_months' => ['nullable', 'integer', 'min:1', 'required_if:payment_type,installment'],
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
            if ($floor > (int) ($property->floors_count ?? 0)) {
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
        $remaining = max(0, $salePrice - $downPaymentValue);
        $monthly = $installmentMonths ? round($remaining / $installmentMonths, 2) : 0;

        $this->merge([
            'sale_date' => $this->input('sale_date', now()->toDateString()),
            'down_payment' => $downPaymentValue,
            'installment_plan' => $isCash ? null : [
                'remaining_amount' => $remaining,
                'monthly_installment' => $monthly,
            ],
        ]);
    }
}
