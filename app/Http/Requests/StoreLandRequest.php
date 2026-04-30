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

        $asNullableNonNegativeFloat = function (string $key): ?float {
            if (! filled($this->input($key))) {
                return null;
            }

            return max(0, (float) $this->input($key));
        };

        $this->merge([
            'name' => $name,
            'land_cost' => $asNullableNonNegativeFloat('land_cost'),
            'building_license_cost' => $asNullableNonNegativeFloat('building_license_cost'),
            'piles_cost' => $asNullableNonNegativeFloat('piles_cost'),
            'excavation_cost' => $asNullableNonNegativeFloat('excavation_cost'),
            'gravel_cost' => $asNullableNonNegativeFloat('gravel_cost'),
            'sand_cost' => $asNullableNonNegativeFloat('sand_cost'),
            'cement_cost' => $asNullableNonNegativeFloat('cement_cost'),
            'steel_cost' => $asNullableNonNegativeFloat('steel_cost'),
            'carpentry_labor_cost' => $asNullableNonNegativeFloat('carpentry_labor_cost'),
            'blacksmith_labor_cost' => $asNullableNonNegativeFloat('blacksmith_labor_cost'),
            'mason_labor_cost' => $asNullableNonNegativeFloat('mason_labor_cost'),
            'electrician_labor_cost' => $asNullableNonNegativeFloat('electrician_labor_cost'),
            'tips_cost' => $asNullableNonNegativeFloat('tips_cost'),
            'notes' => filled($this->input('notes')) ? trim((string) $this->input('notes')) : null,
        ]);
    }
}
