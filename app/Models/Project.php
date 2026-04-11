<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'is_draft',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_draft' => 'boolean',
        ];
    }

    /** مشاريع تظهر في الشريط الجانبي والتنقل (ليست مسودة). */
    public function scopeListed($query)
    {
        return $query->where('is_active', true)->where('is_draft', false);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
