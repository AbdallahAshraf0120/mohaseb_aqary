<?php

namespace App\Http\Requests;

use App\Support\CurrentProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = app(CurrentProject::class)->id();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lands', 'name')->where(fn ($query) => $query->where('project_id', $projectId)),
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

    protected function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name', ''));

        $this->merge([
            'name' => $name,
            'land_cost' => max(0, (float) $this->input('land_cost', 0)),
            'building_license_cost' => max(0, (float) $this->input('building_license_cost', 0)),
            'piles_cost' => max(0, (float) $this->input('piles_cost', 0)),
            'excavation_cost' => max(0, (float) $this->input('excavation_cost', 0)),
            'gravel_cost' => max(0, (float) $this->input('gravel_cost', 0)),
            'sand_cost' => max(0, (float) $this->input('sand_cost', 0)),
            'cement_cost' => max(0, (float) $this->input('cement_cost', 0)),
            'steel_cost' => max(0, (float) $this->input('steel_cost', 0)),
            'carpentry_labor_cost' => max(0, (float) $this->input('carpentry_labor_cost', 0)),
            'blacksmith_labor_cost' => max(0, (float) $this->input('blacksmith_labor_cost', 0)),
            'mason_labor_cost' => max(0, (float) $this->input('mason_labor_cost', 0)),
            'electrician_labor_cost' => max(0, (float) $this->input('electrician_labor_cost', 0)),
            'tips_cost' => max(0, (float) $this->input('tips_cost', 0)),
            'notes' => filled($this->input('notes')) ? trim((string) $this->input('notes')) : null,
        ]);
    }
}
