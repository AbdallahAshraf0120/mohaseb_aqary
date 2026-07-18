<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateShareholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $shareholder = $this->route('shareholder');
        if (! $shareholder instanceof Shareholder) {
            return;
        }

        $project = Project::query()->find((int) $shareholder->project_id);
        if ($project === null) {
            return;
        }

        $investment = round((float) $this->input('total_investment', 0), 2);
        $this->merge([
            'share_percentage' => $project->shareholderPercentageForInvestment($investment),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'share_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'total_investment' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('total_investment')) {
                return;
            }

            $shareholder = $this->route('shareholder');
            if (! $shareholder instanceof Shareholder) {
                return;
            }

            $project = Project::query()->find((int) $shareholder->project_id);
            if ($project === null) {
                return;
            }

            $projectCapital = round((float) $project->capital, 2);
            if ($projectCapital <= 0) {
                $validator->errors()->add(
                    'total_investment',
                    'يجب تعيين رأس مال المشروع أولاً من صفحة المشاريع.'
                );

                return;
            }

            $investment = round((float) $this->input('total_investment'), 2);
            $existingInvestment = round((float) Shareholder::withoutProjectScope()
                ->where('project_id', (int) $project->id)
                ->where('id', '!=', (int) $shareholder->id)
                ->sum('total_investment'), 2);

            if (round($existingInvestment + $investment, 2) > $projectCapital) {
                $remaining = max(0, round($projectCapital - $existingInvestment, 2));
                $validator->errors()->add(
                    'total_investment',
                    "مجموع تمويلات المساهمين يتجاوز رأس مال المشروع ({$projectCapital} ج.م). المتبقي المتاح: {$remaining} ج.م."
                );

                return;
            }

            $pct = $project->shareholderPercentageForInvestment($investment);
            $existingPct = round((float) Shareholder::withoutProjectScope()
                ->where('project_id', (int) $project->id)
                ->where('id', '!=', (int) $shareholder->id)
                ->sum('share_percentage'), 2);

            if (round($existingPct + $pct, 2) > 100) {
                $remaining = max(0, round(100 - $existingPct, 2));
                $validator->errors()->add(
                    'total_investment',
                    "مجموع نسب المساهمين سيتجاوز 100%. المتبقي تقريبًا: {$remaining}%."
                );
            }
        });
    }
}
