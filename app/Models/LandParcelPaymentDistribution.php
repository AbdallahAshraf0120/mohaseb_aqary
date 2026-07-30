<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandParcelPaymentDistribution extends Model
{
    public const BASIS_PLANNED = 'planned';

    public const BASIS_ACTUAL = 'actual';

    public const BASIS_MANUAL = 'manual';

    public const BASIS_LEGACY = 'legacy';

    protected $fillable = [
        'land_parcel_payment_id',
        'shareholder_id',
        'amount',
        'basis',
        'percentage_used',
        'ledger_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:5',
            'percentage_used' => 'decimal:5',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(LandParcelPayment::class, 'land_parcel_payment_id');
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(ShareholderLedgerEntry::class, 'ledger_entry_id');
    }

    public function basisLabel(): string
    {
        return match ($this->basis) {
            self::BASIS_PLANNED => 'مخطط',
            self::BASIS_ACTUAL => 'فعلي',
            self::BASIS_MANUAL => 'يدوي',
            self::BASIS_LEGACY => 'قديم',
            default => (string) $this->basis,
        };
    }
}
