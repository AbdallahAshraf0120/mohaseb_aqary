<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'creditor_name' => ['required', 'string', 'max:255'],
            'purchase_description' => ['nullable', 'string', 'max:2000'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $total = (float) $this->input('total_amount', 0);
            $paid = (float) ($this->input('paid_amount', 0) ?? 0);
            if ($paid - $total > 0.009) {
                $validator->errors()->add('paid_amount', 'المسدَّد لا يمكن أن يتجاوز إجمالي الشراء.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('paid_amount') && $this->input('paid_amount') === '') {
            $this->merge(['paid_amount' => 0]);
        }
    }
}
