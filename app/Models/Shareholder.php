<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shareholder extends Model
{
    protected $fillable = ['name'];

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectShareholder::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_shareholder')
            ->withPivot(['share_percentage', 'total_investment'])
            ->withTimestamps();
    }

    public function membershipFor(int $projectId): ?ProjectShareholder
    {
        return $this->projectMemberships()->where('project_id', $projectId)->first();
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
        return $this->hasMany(ShareholderLedgerEntry::class)->withoutGlobalScope('project');
    }

    public function ledgerBalance(?int $projectId = null): float
    {
        $query = $this->ledgerEntries();
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        $credit = (float) (clone $query)->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT)->sum('amount');
        $debit = (float) (clone $query)->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT)->sum('amount');

        return round($credit - $debit, 2);
    }

    public function capitalDepositsTotal(?int $projectId = null): float
    {
        $query = $this->ledgerEntries()->where('type', ShareholderLedgerEntry::TYPE_CAPITAL);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return round((float) $query->sum('amount'), 2);
    }
}
