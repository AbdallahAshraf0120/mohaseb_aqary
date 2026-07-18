<?php

namespace App\Http\Requests;

use App\Models\Project;
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

    protected function prepareForValidation(): void
    {
        $project = $this->resolveProject();
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
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($q) => $q->where('is_draft', false)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'share_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'total_investment' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['project_id', 'total_investment'])) {
                return;
            }

            $project = $this->resolveProject();
            if ($project === null) {
                return;
            }

            $projectCapital = round((float) $project->capital, 2);
            if ($projectCapital <= 0) {
                $validator->errors()->add(
                    'project_id',
                    'يجب تعيين رأس مال المشروع أولاً من صفحة المشاريع قبل إضافة مساهمين.'
                );

                return;
            }

            $investment = round((float) $this->input('total_investment'), 2);
            $existingInvestment = round((float) Shareholder::withoutProjectScope()
                ->where('project_id', (int) $project->id)
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
                ->sum('share_percentage'), 2);

            if (round($existingPct + $pct, 2) > 100) {
                $remaining = max(0, round(100 - $existingPct, 2));
                $validator->errors()->add(
                    'total_investment',
                    "مجموع نسب المساهمين سيتجاوز 100%. المتبقي تقريبًا: {$remaining}% من رأس مال المشروع."
                );
            }
        });
    }

    private function resolveProject(): ?Project
    {
        $projectId = (int) $this->input('project_id');
        if ($projectId <= 0) {
            return null;
        }

        return Project::query()->find($projectId);
    }
}
