<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnershipSnapshot extends Model
{
    public const KIND_ADOPT_PLAN = 'adopt_plan';

    public const TARGET_PROJECT = 'project';

    public const TARGET_LAND_PARCEL = 'land_parcel';

    protected $fillable = [
        'target_type',
        'target_id',
        'kind',
        'before',
        'after',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
