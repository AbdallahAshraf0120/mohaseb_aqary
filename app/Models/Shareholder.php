<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shareholder extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'name', 'share_percentage', 'total_investment', 'profit_amount'];

    protected function casts(): array
    {
        return [
            'share_percentage' => 'decimal:2',
            'total_investment' => 'decimal:2',
            'profit_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->ledgers();
    }

    /**
     * Used by scoped route binding for {ledger} (Laravel expects plural: ledgers).
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(ShareholderLedgerEntry::class);
    }

    public function ledgerBalance(): float
    {
        $credit = (float) $this->ledgerEntries()
            ->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT)
            ->sum('amount');
        $debit = (float) $this->ledgerEntries()
            ->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT)
            ->sum('amount');

        return round($credit - $debit, 2);
    }

    public function capitalDepositsTotal(): float
    {
        return round((float) $this->ledgerEntries()
            ->where('type', ShareholderLedgerEntry::TYPE_CAPITAL)
            ->sum('amount'), 2);
    }
}
