<?php

namespace App\Http\Requests;

use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShareholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($q) => $q->where('is_draft', false)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'share_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'total_investment' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['project_id', 'share_percentage'])) {
                return;
            }

            $projectId = (int) $this->input('project_id');
            $newPct = round((float) $this->input('share_percentage'), 2);
            $existing = round((float) Shareholder::withoutProjectScope()
                ->where('project_id', $projectId)
                ->sum('share_percentage'), 2);

            if (round($existing + $newPct, 2) > 100) {
                $remaining = max(0, round(100 - $existing, 2));
                $validator->errors()->add(
                    'share_percentage',
                    "مجموع نسب المساهمين في هذا المشروع لا يجوز أن يتجاوز 100%. المسجّل حالياً: {$existing}% — المتبقي المتاح: {$remaining}%."
                );
            }
        });
    }
}
