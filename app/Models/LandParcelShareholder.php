<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandParcelShareholder extends Model
{
    protected $table = 'land_parcel_shareholder';

    protected $fillable = [
        'land_parcel_id',
        'shareholder_id',
        'share_percentage',
        'total_investment',
    ];

    protected function casts(): array
    {
        return [
            'share_percentage' => 'decimal:2',
            'total_investment' => 'decimal:2',
        ];
    }

    public function landParcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'land_parcel_id');
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }
}
