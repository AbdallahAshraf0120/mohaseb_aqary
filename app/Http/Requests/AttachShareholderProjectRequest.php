<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
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
            'share_percentage' => ['required', 'numeric', 'min:0.00001', 'max:100'],
            'total_investment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['project_id', 'share_percentage'])) {
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

            $pctColumn = Schema::hasColumn('project_shareholder', 'planned_percentage')
                ? 'planned_percentage'
                : 'share_percentage';
            $percentage = round((float) $this->input('share_percentage'), 5);
            $existingPct = round((float) ProjectShareholder::query()
                ->where('project_id', $projectId)
                ->sum($pctColumn), 5);

            if (round($existingPct + $percentage, 5) > 100.00001) {
                $remaining = max(0, round(100 - $existingPct, 5));
                $validator->errors()->add(
                    'share_percentage',
                    "مجموع نسب المساهمين يتجاوز 100٪. المتبقي: {$remaining}٪."
                );
            }
        });
    }
}
