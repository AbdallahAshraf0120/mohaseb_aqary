<?php

namespace App\Http\Requests;

use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateShareholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'share_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'total_investment' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('share_percentage')) {
                return;
            }

            $shareholder = $this->route('shareholder');
            if (! $shareholder instanceof Shareholder) {
                return;
            }

            $newPct = round((float) $this->input('share_percentage'), 2);
            $existing = round((float) Shareholder::withoutProjectScope()
                ->where('project_id', (int) $shareholder->project_id)
                ->where('id', '!=', (int) $shareholder->id)
                ->sum('share_percentage'), 2);

            if (round($existing + $newPct, 2) > 100) {
                $remaining = max(0, round(100 - $existing, 2));
                $validator->errors()->add(
                    'share_percentage',
                    "مجموع نسب المساهمين في هذا المشروع لا يجوز أن يتجاوز 100%. باقي المساهمين: {$existing}% — المتبقي المتاح: {$remaining}%."
                );
            }
        });
    }
}
