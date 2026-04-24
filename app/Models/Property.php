<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'name',
        'area_id',
        'land_id',
        'property_type',
        'land_name',
        'building_total_floors',
        'floors_count',
        'registered_floors',
        'mezzanine_floors',
        'mushaa_partner_name',
        'mushaa_floors',
        'apartments_per_floor',
        'ground_floor_shops_count',
        'has_mezzanine',
        'mezzanine_apartments_count',
        'total_apartments',
        'shareholder_allocations',
        'apartment_models',
        'location',
        'price',
        'status',
        'owner_id',
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
    ];

    protected $casts = [
        'has_mezzanine' => 'boolean',
        'registered_floors' => 'array',
        'mezzanine_floors' => 'array',
        'shareholder_allocations' => 'array',
        'apartment_models' => 'array',
        'mushaa_floors' => 'array',
        'land_cost' => 'decimal:2',
        'building_license_cost' => 'decimal:2',
        'piles_cost' => 'decimal:2',
        'excavation_cost' => 'decimal:2',
        'gravel_cost' => 'decimal:2',
        'sand_cost' => 'decimal:2',
        'cement_cost' => 'decimal:2',
        'steel_cost' => 'decimal:2',
        'carpentry_labor_cost' => 'decimal:2',
        'blacksmith_labor_cost' => 'decimal:2',
        'mason_labor_cost' => 'decimal:2',
        'electrician_labor_cost' => 'decimal:2',
        'tips_cost' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function land(): BelongsTo
    {
        return $this->belongsTo(Land::class);
    }

    /** @return list<int> */
    public function mushaaFloorNumbers(): array
    {
        return collect($this->mushaa_floors ?? [])
            ->map(static fn ($n) => (int) $n)
            ->filter(static fn (int $n) => $n >= 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function hasMushaaFloors(): bool
    {
        return $this->mushaaFloorNumbers() !== [];
    }

    /** @var list<string> */
    private const DEVELOPMENT_COST_ATTRIBUTES = [
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
    ];

    /**
     * مجموع حقول التكلفة المسجّلة على العقار (يُستخدم لتوزيع التزام التكلفة على المساهمين حسب النسبة).
     */
    public function totalRecordedDevelopmentCost(): float
    {
        $sum = 0.0;
        foreach (self::DEVELOPMENT_COST_ATTRIBUTES as $attr) {
            $sum += (float) ($this->getAttribute($attr) ?? 0);
        }

        return round($sum, 2);
    }

    /**
     * أدوار مشاعة مع شريك مسجّل: يُفترض تقسيم عائد وحدات ذلك الدور 50٪ لمجموعة المساهمين و50٪ للشريك.
     */
    public function mushaaFiftyFiftyWithPartnerApplies(): bool
    {
        return $this->hasMushaaFloors() && filled($this->mushaa_partner_name);
    }
}
