<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'area_id' => ['required', 'exists:areas,id'],
            'property_type' => ['required', 'string', 'max:255'],
            'floors_count' => ['required', 'integer', 'min:1'],
            'apartments_per_floor' => ['required', 'integer', 'min:1'],
            'total_apartments' => ['required', 'integer', 'min:1'],
            'shareholder_percentages' => ['nullable', 'array'],
            'shareholder_percentages.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'shareholder_allocations' => ['nullable', 'array'],
            'apartment_models' => ['nullable', 'array'],
            'apartment_models.*.model_name' => ['required_with:apartment_models.*.area', 'nullable', 'string', 'max:255'],
            'apartment_models.*.area' => ['required_with:apartment_models.*.model_name', 'nullable', 'numeric', 'min:1'],
            'location' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,reserved,sold,rented'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $floors = max(1, (int) $this->input('floors_count', 1));
        $apartmentsPerFloor = max(1, (int) $this->input('apartments_per_floor', 1));
        $providedTotal = (int) $this->input('total_apartments', 0);
        $calculatedTotal = $floors * $apartmentsPerFloor;

        $percentages = collect($this->input('shareholder_percentages', []))
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->map(static fn ($value, $id) => [
                'shareholder_id' => (int) $id,
                'percentage' => (float) $value,
                'shareholder_name' => Shareholder::query()->whereKey((int) $id)->value('name'),
            ])
            ->values()
            ->all();

        $models = collect($this->input('apartment_models', []))
            ->filter(static fn (array $item) => !empty($item['model_name']) && !empty($item['area']))
            ->map(static fn (array $item) => [
                'model_name' => trim((string) $item['model_name']),
                'area' => (float) $item['area'],
            ])
            ->values()
            ->all();

        $this->merge([
            'total_apartments' => $providedTotal > 0 ? $providedTotal : $calculatedTotal,
            'shareholder_allocations' => $percentages,
            'apartment_models' => $models,
            // Keep legacy location synced with selected area.
            'location' => Area::query()->whereKey((int) $this->input('area_id'))->value('name') ?: $this->input('location'),
            'price' => $this->input('price', 0),
            'status' => $this->input('status', 'available'),
        ]);
    }
}
