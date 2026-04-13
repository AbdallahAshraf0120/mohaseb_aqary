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
        'property_type',
        'building_total_floors',
        'floors_count',
        'registered_floors',
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
    ];

    protected $casts = [
        'has_mezzanine' => 'boolean',
        'registered_floors' => 'array',
        'shareholder_allocations' => 'array',
        'apartment_models' => 'array',
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
}
