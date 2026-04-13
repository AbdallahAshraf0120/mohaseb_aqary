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
            'building_total_floors' => ['required', 'integer', 'min:1'],
            'floors_count' => ['required', 'integer', 'min:1'],
            'registered_floors' => ['nullable', 'array'],
            'registered_floors.*' => ['nullable', 'integer', 'min:1'],
            'mushaa_floors' => ['nullable', 'array'],
            'mushaa_floors.*' => ['nullable', 'integer', 'min:1'],
            'mezzanine_floors' => ['nullable', 'array'],
            'mezzanine_floors.*.floor_number' => ['nullable', 'integer', 'min:1'],
            'mezzanine_floors.*.apartments_count' => ['nullable', 'integer', 'min:1'],
            'mushaa_partner_name' => ['nullable', 'string', 'max:255'],
            'apartments_per_floor' => ['required', 'integer', 'min:1'],
            'ground_floor_shops_count' => ['nullable', 'integer', 'min:0'],
            'has_mezzanine' => ['nullable', 'boolean'],
            'mezzanine_apartments_count' => ['nullable', 'integer', 'min:0'],
            'total_apartments' => ['required', 'integer', 'min:1'],
            'shareholder_percentages' => ['nullable', 'array'],
            'shareholder_percentages.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'shareholder_allocations' => ['nullable', 'array'],
            'apartment_models' => ['nullable', 'array'],
            'apartment_models.*.model_name' => ['required_with:apartment_models.*.area', 'nullable', 'string', 'max:255'],
            'apartment_models.*.area' => ['required_with:apartment_models.*.model_name', 'nullable', 'numeric', 'min:1'],
            'apartment_models.*.rooms_count' => ['nullable', 'integer', 'min:0'],
            'apartment_models.*.bathrooms_count' => ['nullable', 'integer', 'min:0'],
            'apartment_models.*.view_type' => ['nullable', 'in:normal,facade,corner'],
            'location' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,reserved,sold,rented'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $buildingTotalFloors = max(1, (int) $this->input('building_total_floors', 1));
        $floorsInput = max(1, (int) $this->input('floors_count', 1));
        $registeredFloors = collect($this->input('registered_floors', []))
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn (int $value) => $value >= 1 && $value <= $buildingTotalFloors)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $effectiveFloors = count($registeredFloors) > 0 ? count($registeredFloors) : min($floorsInput, $buildingTotalFloors);
        if (count($registeredFloors) === 0) {
            $registeredFloors = range(1, $effectiveFloors);
        }
        $legacyMushaaFromMezzanineInput = collect($this->input('mezzanine_floors', []))
            ->filter(static fn ($item) => is_array($item)
                && ! empty($item['floor_number'])
                && filter_var($item['is_mushaa'] ?? false, FILTER_VALIDATE_BOOL))
            ->map(static fn (array $item) => (int) $item['floor_number'])
            ->filter(static fn (int $n) => $n >= 1 && $n <= $buildingTotalFloors)
            ->unique();
        $mezzanineFloors = collect($this->input('mezzanine_floors', []))
            ->filter(static fn (array $item) => !empty($item['floor_number']) && !empty($item['apartments_count']))
            ->map(static function (array $item) use ($buildingTotalFloors): ?array {
                $floorNumber = (int) $item['floor_number'];
                $apartmentsCount = (int) $item['apartments_count'];
                if ($floorNumber < 1 || $floorNumber > $buildingTotalFloors || $apartmentsCount < 1) {
                    return null;
                }

                return [
                    'floor_number' => $floorNumber,
                    'apartments_count' => $apartmentsCount,
                ];
            })
            ->filter()
            ->unique(static fn (array $item) => $item['floor_number'])
            ->sortBy('floor_number')
            ->values()
            ->all();
        $allowedFloorNumbersForMushaa = collect($registeredFloors)
            ->merge(collect($mezzanineFloors)->pluck('floor_number'))
            ->map(static fn ($n) => (int) $n)
            ->unique()
            ->flip();
        $mushaaFloors = collect($this->input('mushaa_floors', []))
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn (int $n) => $n >= 1 && $n <= $buildingTotalFloors)
            ->merge($legacyMushaaFromMezzanineInput)
            ->unique()
            ->filter(static fn (int $n) => $allowedFloorNumbersForMushaa->has($n))
            ->sort()
            ->values()
            ->all();
        $apartmentsPerFloor = max(1, (int) $this->input('apartments_per_floor', 1));
        $hasMezzanine = count($mezzanineFloors) > 0 || filter_var($this->input('has_mezzanine', false), FILTER_VALIDATE_BOOL);
        $effectiveMezzanineApartments = count($mezzanineFloors) > 0
            ? (int) collect($mezzanineFloors)->sum('apartments_count')
            : max(0, (int) $this->input('mezzanine_apartments_count', 0));
        $providedTotal = (int) $this->input('total_apartments', 0);
        $calculatedTotal = ($effectiveFloors * $apartmentsPerFloor) + $effectiveMezzanineApartments;

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
                'rooms_count' => max(0, (int) ($item['rooms_count'] ?? 0)),
                'bathrooms_count' => max(0, (int) ($item['bathrooms_count'] ?? 0)),
                'view_type' => in_array(($item['view_type'] ?? 'normal'), ['normal', 'facade', 'corner'], true)
                    ? $item['view_type']
                    : 'normal',
            ])
            ->values()
            ->all();

        $mushaaPartner = $this->input('mushaa_partner_name');
        $mushaaPartner = is_string($mushaaPartner) ? trim($mushaaPartner) : '';
        $mushaaPartner = $mushaaPartner === '' ? null : mb_substr($mushaaPartner, 0, 255);

        $this->merge([
            'mushaa_partner_name' => $mushaaPartner,
            'mushaa_floors' => $mushaaFloors,
            'total_apartments' => $providedTotal > 0 ? $providedTotal : $calculatedTotal,
            'building_total_floors' => $buildingTotalFloors,
            'floors_count' => $effectiveFloors,
            'registered_floors' => $registeredFloors,
            'mezzanine_floors' => $mezzanineFloors,
            'ground_floor_shops_count' => max(0, (int) $this->input('ground_floor_shops_count', 0)),
            'has_mezzanine' => $hasMezzanine,
            'mezzanine_apartments_count' => $effectiveMezzanineApartments,
            'shareholder_allocations' => $percentages,
            'apartment_models' => $models,
            // Keep legacy location synced with selected area.
            'location' => Area::query()->whereKey((int) $this->input('area_id'))->value('name') ?: $this->input('location'),
            'price' => $this->input('price', 0),
            'status' => $this->input('status', 'available'),
        ]);
    }
}
