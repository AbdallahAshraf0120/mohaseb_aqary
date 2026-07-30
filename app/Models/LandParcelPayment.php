<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandParcelPayment extends Model
{
    public const SIDE_PURCHASE = 'purchase';

    public const SIDE_SALE = 'sale';

    public const KIND_DOWN_PAYMENT = 'down_payment';

    public const KIND_INSTALLMENT = 'installment';

    public const KIND_SECONDARY = 'secondary';

    public const KIND_OTHER = 'other';

    protected $fillable = [
        'land_parcel_id',
        'land_parcel_part_id',
        'side',
        'kind',
        'amount',
        'paid_at',
        'payment_method',
        'notes',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'created_by',
        'paid_by_shareholder_id',
        'received_by_shareholder_id',
        'distribution_status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:5',
            'paid_at' => 'date',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function landParcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'land_parcel_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(LandParcelPart::class, 'land_parcel_part_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paidByShareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class, 'paid_by_shareholder_id');
    }

    public function receivedByShareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class, 'received_by_shareholder_id');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(LandParcelPaymentDistribution::class, 'land_parcel_payment_id');
    }

    public function isDistributionPending(): bool
    {
        return $this->side === self::SIDE_SALE
            && ($this->approval_status ?? '') === 'approved'
            && ($this->distribution_status ?? 'pending') === 'pending';
    }

    public function sideLabel(): string
    {
        return match ($this->side) {
            self::SIDE_PURCHASE => 'شراء',
            self::SIDE_SALE => 'بيع',
            default => (string) $this->side,
        };
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            self::KIND_DOWN_PAYMENT => 'مقدم',
            self::KIND_INSTALLMENT => 'قسط',
            self::KIND_SECONDARY => 'دفعة ثانوية',
            default => 'أخرى',
        };
    }
}
