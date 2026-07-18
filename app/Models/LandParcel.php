<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandParcel extends Model
{
    public const STATUSES = [
        'owned' => 'مملوكة',
        'for_sale' => 'للبيع',
        'reserved' => 'محجوزة',
        'sold' => 'مباعة',
        'cancelled' => 'ملغاة',
    ];

    protected $fillable = [
        'created_by',
        'name',
        'location',
        'city',
        'area_size',
        'deed_number',
        'status',
        'purchase_price',
        'purchase_date',
        'purchased_from',
        'purchase_phone',
        'sale_price',
        'sale_date',
        'sold_to',
        'sale_phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'area_size' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'purchase_date' => 'date',
            'sale_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parcelShareholders(): HasMany
    {
        return $this->hasMany(LandParcelShareholder::class, 'land_parcel_id');
    }

    public function shareholders(): BelongsToMany
    {
        return $this->belongsToMany(Shareholder::class, 'land_parcel_shareholder', 'land_parcel_id', 'shareholder_id')
            ->withPivot(['share_percentage', 'total_investment'])
            ->withTimestamps();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function profit(): ?float
    {
        if ($this->sale_price === null) {
            return null;
        }

        return (float) $this->sale_price - (float) $this->purchase_price;
    }

    /** أساس النسبة: سعر شراء الأرض (مثل رأس مال المشروع). */
    public function shareholderPercentageForInvestment(float|int|string $investment): float
    {
        $capital = round((float) $this->purchase_price, 2);
        if ($capital <= 0) {
            return 0.0;
        }

        return round(((float) $investment / $capital) * 100, 2);
    }
}
