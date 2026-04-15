<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Land extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'area_id',
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
        'notes',
    ];

    protected $casts = [
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

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
