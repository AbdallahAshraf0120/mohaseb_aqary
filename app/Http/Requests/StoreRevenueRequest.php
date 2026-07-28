<?php

namespace App\Http\Requests;

use App\Models\Contract;
use App\Models\ProjectShareholder;
use App\Models\Revenue;
use App\Support\CurrentProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

class StoreRevenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
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

        if (Schema::hasColumn('revenues', 'received_by_shareholder_id')) {
            $rules['received_by_shareholder_id'] = ['nullable', 'integer', 'exists:shareholders,id'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (Schema::hasColumn('revenues', 'received_by_shareholder_id')) {
                $shareholderId = $this->input('received_by_shareholder_id');
                $projectId = app(CurrentProject::class)->id();
                if ($shareholderId !== null && $shareholderId !== '' && $projectId !== null) {
                    $member = ProjectShareholder::query()
                        ->where('project_id', $projectId)
                        ->where('shareholder_id', (int) $shareholderId)
                        ->exists();
                    if (! $member) {
                        $validator->errors()->add('received_by_shareholder_id', 'المساهم (دخل حسابه) غير مرتبط بهذا المشروع.');
                    }
                }
            }

            $contract = Contract::query()->find((int) $this->input('contract_id'));
            if (! $contract) {
                return;
            }

            if ((int) $this->input('client_id') !== (int) $contract->client_id) {
                $validator->errors()->add('client_id', 'العميل لا يطابق العقد المختار.');
            }

            $amount = (float) $this->input('amount', 0);
            $pendingRequested = (float) Revenue::query()
                ->where('contract_id', $contract->id)
                ->where('approval_status', 'pending')
                ->sum('amount');
            $available = max(0.0, (float) $contract->remaining_amount - $pendingRequested);
            if ($amount - $available > 0.009) {
                $validator->errors()->add('amount', 'قيمة التحصيل أكبر من المتبقي في العقد بعد احتساب العمليات المعلّقة.');
            }

            if ($validator->errors()->has('amount')) {
                return;
            }

            $contract->loadMissing('sale');
            $sale = $contract->sale;
            if ($sale === null) {
                return;
            }

            $down = $sale->approval_status === 'approved' ? round((float) ($sale->down_payment ?? 0), 2) : 0.0;
            if ($down < 0.01) {
                return;
            }

            $revenuesQuery = Revenue::query()
                ->where('contract_id', $contract->id)
                ->where('approval_status', 'approved');
            $routeRevenue = $this->route('revenue');
            if ($routeRevenue instanceof Revenue) {
                $revenuesQuery->where('id', '!=', (int) $routeRevenue->getKey());
            }

            if ($revenuesQuery->count() > 0) {
                return;
            }

            if (abs($amount - $down) < 0.02) {
                $validator->errors()->add(
                    'amount',
                    'المبلغ يطابق المقدم المسجّل من البيعة وهذا أوّل تحصيل على العقد؛ لا تُسجَّل المقدم مرة أخرى كتحصيل. سجّل الأقساط بعد المقدم فقط.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('received_by_shareholder_id') === '') {
            $this->merge(['received_by_shareholder_id' => null]);
        }

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
