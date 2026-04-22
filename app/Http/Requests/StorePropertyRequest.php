<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Models\Facing;
use App\Models\Land;
use App\Models\Shareholder;
use App\Support\CurrentProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = (int) app(CurrentProject::class)->id();
        $viewFacingRule = $projectId > 0
            ? ['nullable', Rule::exists('facings', 'code')->where(fn ($q) => $q->where('project_id', $projectId))]
            : ['nullable', 'string', 'max:64'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'area_id' => ['required', 'exists:areas,id'],
            'land_id' => ['nullable', 'exists:lands,id'],
            'property_type' => ['required', 'string', 'max:255'],
            'land_name' => ['nullable', 'string', 'max:255'],
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
            'apartment_models.*.view_type' => $viewFacingRule,
            'location' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,reserved,sold,rented'],
            'owner_id' => ['nullable', 'exists:users,id'],
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

        $projectId = (int) app(CurrentProject::class)->id();
        if ($projectId > 0 && Facing::query()->where('project_id', $projectId)->doesntExist()) {
            Facing::seedDefaultsForProject($projectId);
        }
        $allowedFacingCodes = $projectId > 0
            ? Facing::query()->where('project_id', $projectId)->orderBy('sort_order')->orderBy('id')->pluck('code')->all()
            : [];
        $defaultFacingCode = $allowedFacingCodes[0] ?? 'normal';

        $models = collect($this->input('apartment_models', []))
            ->filter(static fn (array $item) => !empty($item['model_name']) && !empty($item['area']))
            ->map(static function (array $item) use ($allowedFacingCodes, $defaultFacingCode): array {
                $vt = (string) ($item['view_type'] ?? $defaultFacingCode);
                if ($allowedFacingCodes !== [] && ! in_array($vt, $allowedFacingCodes, true)) {
                    $vt = $defaultFacingCode;
                }

                return [
                    'model_name' => trim((string) $item['model_name']),
                    'area' => (float) $item['area'],
                    'rooms_count' => max(0, (int) ($item['rooms_count'] ?? 0)),
                    'bathrooms_count' => max(0, (int) ($item['bathrooms_count'] ?? 0)),
                    'view_type' => $vt,
                ];
            })
            ->values()
            ->all();

        $mushaaPartner = $this->input('mushaa_partner_name');
        $mushaaPartner = is_string($mushaaPartner) ? trim($mushaaPartner) : '';
        $mushaaPartner = $mushaaPartner === '' ? null : mb_substr($mushaaPartner, 0, 255);
        $landId = (int) $this->input('land_id', 0);
        $land = $landId > 0
            ? Land::query()->select([
                'id',
                'name',
                'land_cost',
                'building_license_cost',
                'piles_cost',
                'excavation_cost',
                'gravel_cost',
                'sand_cost',
                'cement_cost',
                'steel_cost',
                'carpentry_labor_cost',
                'blacksmith_labor_cost',
                'mason_labor_cost',
                'electrician_labor_cost',
                'tips_cost',
            ])->find($landId)
            : null;
        $landName = $this->input('land_name');
        $landName = is_string($landName) ? trim($landName) : '';
        $landName = $landName === '' ? ($land?->name ?? null) : mb_substr($landName, 0, 255);

        $costValue = static function (mixed $inputValue, float $fallback): float {
            return ($inputValue !== null && $inputValue !== '')
                ? max(0, (float) $inputValue)
                : max(0, $fallback);
        };

        $this->merge([
            'land_id' => $land?->id,
            'area_id' => (int) $this->input('area_id') > 0 ? (int) $this->input('area_id') : ($land?->area_id ?? null),
            'land_name' => $landName,
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
            'land_cost' => $costValue($this->input('land_cost'), (float) ($land?->land_cost ?? 0)),
            'building_license_cost' => $costValue($this->input('building_license_cost'), (float) ($land?->building_license_cost ?? 0)),
            'piles_cost' => $costValue($this->input('piles_cost'), (float) ($land?->piles_cost ?? 0)),
            'excavation_cost' => $costValue($this->input('excavation_cost'), (float) ($land?->excavation_cost ?? 0)),
            'gravel_cost' => $costValue($this->input('gravel_cost'), (float) ($land?->gravel_cost ?? 0)),
            'sand_cost' => $costValue($this->input('sand_cost'), (float) ($land?->sand_cost ?? 0)),
            'cement_cost' => $costValue($this->input('cement_cost'), (float) ($land?->cement_cost ?? 0)),
            'steel_cost' => $costValue($this->input('steel_cost'), (float) ($land?->steel_cost ?? 0)),
            'carpentry_labor_cost' => $costValue($this->input('carpentry_labor_cost'), (float) ($land?->carpentry_labor_cost ?? 0)),
            'blacksmith_labor_cost' => $costValue($this->input('blacksmith_labor_cost'), (float) ($land?->blacksmith_labor_cost ?? 0)),
            'mason_labor_cost' => $costValue($this->input('mason_labor_cost'), (float) ($land?->mason_labor_cost ?? 0)),
            'electrician_labor_cost' => $costValue($this->input('electrician_labor_cost'), (float) ($land?->electrician_labor_cost ?? 0)),
            'tips_cost' => $costValue($this->input('tips_cost'), (float) ($land?->tips_cost ?? 0)),
        ]);
    }
}
