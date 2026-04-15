<?php

namespace App\Http\Requests;

use App\Models\Land;
use App\Support\CurrentProject;
use Illuminate\Validation\Rule;

class UpdateLandRequest extends StoreLandRequest
{
    public function rules(): array
    {
        $projectId = app(CurrentProject::class)->id();
        $land = $this->route('land');
        $landId = $land instanceof Land ? $land->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lands', 'name')
                    ->ignore($landId)
                    ->where(fn ($query) => $query->where('project_id', $projectId)),
            ],
            'area_id' => ['nullable', 'exists:areas,id'],
            'land_cost' => ['nullable', 'numeric', 'min:0'],
            'building_license_cost' => ['nullable', 'numeric', 'min:0'],
            'piles_cost' => ['nullable', 'numeric', 'min:0'],
            'excavation_cost' => ['nullable', 'numeric', 'min:0'],
            'gravel_cost' => ['nullable', 'numeric', 'min:0'],
            'sand_cost' => ['nullable', 'numeric', 'min:0'],
            'cement_cost' => ['nullable', 'numeric', 'min:0'],
            'steel_cost' => ['nullable', 'numeric', 'min:0'],
            'carpentry_labor_cost' => ['nullable', 'numeric', 'min:0'],
            'blacksmith_labor_cost' => ['nullable', 'numeric', 'min:0'],
            'mason_labor_cost' => ['nullable', 'numeric', 'min:0'],
            'electrician_labor_cost' => ['nullable', 'numeric', 'min:0'],
            'tips_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
