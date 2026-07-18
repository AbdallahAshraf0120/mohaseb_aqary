<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttachShareholderProjectRequest extends FormRequest
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
            'total_investment' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['project_id', 'total_investment'])) {
                return;
            }

            $shareholder = $this->route('shareholder');
            if (! $shareholder instanceof Shareholder) {
                return;
            }

            $projectId = (int) $this->input('project_id');
            $already = ProjectShareholder::query()
                ->where('shareholder_id', (int) $shareholder->id)
                ->where('project_id', $projectId)
                ->exists();
            if ($already) {
                $validator->errors()->add('project_id', 'هذا المساهم مرتبط بهذا المشروع بالفعل.');

                return;
            }

            $project = Project::query()->find($projectId);
            if ($project === null) {
                return;
            }

            $projectCapital = round((float) $project->capital, 2);
            if ($projectCapital <= 0) {
                $validator->errors()->add(
                    'project_id',
                    'يجب تعيين رأس مال المشروع أولاً.'
                );

                return;
            }

            $investment = round((float) $this->input('total_investment'), 2);
            $existingInvestment = round((float) ProjectShareholder::query()
                ->where('project_id', $projectId)
                ->sum('total_investment'), 2);

            if (round($existingInvestment + $investment, 2) > $projectCapital) {
                $remaining = max(0, round($projectCapital - $existingInvestment, 2));
                $validator->errors()->add(
                    'total_investment',
                    "مجموع تمويلات المساهمين يتجاوز رأس مال المشروع. المتبقي: {$remaining} ج.م."
                );
            }
        });
    }
}
