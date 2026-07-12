<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
