<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareholderLedgerEntry extends Model
{
    use BelongsToProject;

    public const TYPE_CAPITAL = 'capital';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_DISTRIBUTION = 'distribution';

    public const TYPE_SETTLEMENT = 'settlement';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPES = [
        self::TYPE_CAPITAL => 'إيداع رأس مال',
        self::TYPE_WITHDRAWAL => 'سحب',
        self::TYPE_DISTRIBUTION => 'توزيع أرباح',
        self::TYPE_SETTLEMENT => 'تصفية مدفوعة',
        self::TYPE_ADJUSTMENT => 'تسوية',
    ];

    public const DIRECTION_CREDIT = 'credit';

    public const DIRECTION_DEBIT = 'debit';

    protected $fillable = [
        'project_id',
        'land_parcel_id',
        'land_parcel_payment_id',
        'revenue_id',
        'sale_id',
        'source_ledger_entry_id',
        'shareholder_id',
        'type',
        'direction',
        'amount',
        'entry_date',
        'notes',
        'created_by',
        'treasury_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:5',
            'entry_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function landParcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'land_parcel_id');
    }

    public function landParcelPayment(): BelongsTo
    {
        return $this->belongsTo(LandParcelPayment::class, 'land_parcel_payment_id');
    }

    public function revenue(): BelongsTo
    {
        return $this->belongsTo(Revenue::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function sourceLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_ledger_entry_id');
    }

    public function allocations()
    {
        return $this->hasMany(self::class, 'source_ledger_entry_id');
    }

    /**
     * المبلغ المتبقي القابل للتوزيع من حركة دائنة على مشروع.
     */
    public function remainingAllocatableAmount(): float
    {
        if ($this->direction !== self::DIRECTION_CREDIT || $this->project_id === null) {
            return 0.0;
        }

        $sourceAmount = round((float) $this->amount, 5);
        if (! \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'source_ledger_entry_id')) {
            return $sourceAmount;
        }

        $allocated = round((float) static::withoutProjectScope()
            ->where('source_ledger_entry_id', (int) $this->id)
            ->where('direction', self::DIRECTION_DEBIT)
            ->where('project_id', (int) $this->project_id)
            ->sum('amount'), 5);

        return round(max(0, $sourceAmount - $allocated), 5);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutProjectScope()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treasuryTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'treasury_transaction_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    public function signedAmount(): float
    {
        $amount = (float) $this->amount;

        return $this->direction === self::DIRECTION_CREDIT ? $amount : -$amount;
    }

    public static function defaultDirectionForType(string $type): string
    {
        return match ($type) {
            self::TYPE_CAPITAL => self::DIRECTION_CREDIT,
            self::TYPE_WITHDRAWAL, self::TYPE_DISTRIBUTION, self::TYPE_SETTLEMENT => self::DIRECTION_DEBIT,
            default => self::DIRECTION_CREDIT,
        };
    }

    public static function affectsCashbox(string $type): bool
    {
        return in_array($type, [
            self::TYPE_CAPITAL,
            self::TYPE_WITHDRAWAL,
            self::TYPE_DISTRIBUTION,
            self::TYPE_SETTLEMENT,
        ], true);
    }

    public static function cashboxTypeFor(string $type, string $direction): ?string
    {
        if (! self::affectsCashbox($type)) {
            return null;
        }

        if ($type === self::TYPE_CAPITAL) {
            return 'revenue';
        }

        if (in_array($type, [self::TYPE_WITHDRAWAL, self::TYPE_DISTRIBUTION, self::TYPE_SETTLEMENT], true)) {
            return 'expense';
        }

        return $direction === self::DIRECTION_CREDIT ? 'revenue' : 'expense';
    }
}
