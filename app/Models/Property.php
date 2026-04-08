<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'name',
        'area_id',
        'property_type',
        'floors_count',
        'apartments_per_floor',
        'total_apartments',
        'shareholder_allocations',
        'apartment_models',
        'location',
        'price',
        'status',
        'owner_id',
    ];

    protected $casts = [
        'shareholder_allocations' => 'array',
        'apartment_models' => 'array',
    ];

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
