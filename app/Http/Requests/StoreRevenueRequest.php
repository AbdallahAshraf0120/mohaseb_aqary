<?php

namespace App\Http\Requests;

use App\Models\Contract;
use App\Models\Revenue;
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
            'amount' => ['required', 'numeric', 'min:0.01'],
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

    protected function prepareForValidation(): void
    {
        if (! $this->filled('contract_id')) {
            return;
        }

        $amount = $this->input('amount');
        if ($amount !== null && $amount !== '') {
            return;
        }

        $contract = Contract::query()
            ->with([
                'sale',
                'revenues' => static fn ($q) => $q->orderBy('paid_at')->orderBy('id'),
            ])
            ->find((int) $this->input('contract_id'));

        if (! $contract) {
            return;
        }

        $excludeRevenueId = null;
        $routeRevenue = $this->route('revenue');
        if ($routeRevenue instanceof Revenue) {
            $excludeRevenueId = (int) $routeRevenue->getKey();
        }

        $suggested = $contract->suggestedNextCollectionAmount($excludeRevenueId);
        if ($suggested !== null && $suggested >= 0.01) {
            $this->merge(['amount' => $suggested]);
        }
    }
}
