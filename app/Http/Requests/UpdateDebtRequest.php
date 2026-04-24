<?php

namespace App\Http\Requests;

use App\Models\Debt;
use Illuminate\Validation\Validator;

class UpdateDebtRequest extends StoreDebtRequest
{
    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $debt = $this->route('debt');
            if (! $debt instanceof Debt) {
                return;
            }
            $sumPayments = (float) $debt->debtPayments()->sum('amount');
            if ($sumPayments <= 0) {
                return;
            }
            $paid = (float) ($this->input('paid_amount', 0) ?? 0);
            if ($paid + 0.009 < $sumPayments) {
                $v->errors()->add(
                    'paid_amount',
                    'المسدَّد الإجمالي لا يمكن أن يقل عن مجموع سدادات الصندوق ('.number_format($sumPayments, 2).' ج.م).'
                );
            }
        });
    }
}
