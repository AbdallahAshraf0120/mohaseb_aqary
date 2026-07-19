<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundTransfer extends Model
{
    public const TYPE_PROJECT = 'project';

    public const TYPE_LAND_CASHBOX = 'land_cashbox';

    protected $fillable = [
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'amount',
        'transferred_at',
        'notes',
        'shareholder_id',
        'source_land_parcel_id',
        'from_treasury_transaction_id',
        'to_treasury_transaction_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transferred_at' => 'date',
        ];
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function sourceLandParcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'source_land_parcel_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fromTreasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'from_treasury_transaction_id');
    }

    public function toTreasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'to_treasury_transaction_id');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_LAND_CASHBOX => 'صندوق الأراضي',
            self::TYPE_PROJECT => 'صندوق مشروع',
            default => $type,
        };
    }
}
