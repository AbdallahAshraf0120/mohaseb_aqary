<?php

namespace App\Models;

use App\Services\CashboxLedgerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    protected $fillable = [
        'debt_id',
        'amount',
        'note',
    ];

    protected static function booted(): void
    {
        static::deleting(function (DebtPayment $payment): void {
            app(CashboxLedgerService::class)->removeDebtPayment((int) $payment->getKey());
        });
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }
}
