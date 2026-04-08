<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRevenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'exists:contracts,id'],
            'sale_id' => ['nullable', 'exists:sales,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'category' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,check'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $contract = Contract::query()->find((int) $this->input('contract_id'));
            if (! $contract) {
                return;
            }

            if ((int) $this->input('client_id') !== (int) $contract->client_id) {
                $validator->errors()->add('client_id', 'العميل لا يطابق العقد المختار.');
            }

            $amount = (float) $this->input('amount', 0);
            if ($amount > (float) $contract->remaining_amount) {
                $validator->errors()->add('amount', 'قيمة التحصيل أكبر من المتبقي في العقد.');
            }
        });
    }
}
