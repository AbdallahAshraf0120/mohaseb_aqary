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
            ->withPivot([
                'share_percentage',
                'total_investment',
                'planned_investment',
                'planned_percentage',
                'actual_investment',
                'actual_percentage',
            ])
            ->withTimestamps();
    }

    public function landMemberships(): HasMany
    {
        return $this->hasMany(LandParcelShareholder::class);
    }

    public function landParcels(): BelongsToMany
    {
        return $this->belongsToMany(LandParcel::class, 'land_parcel_shareholder', 'shareholder_id', 'land_parcel_id')
            ->withPivot([
                'share_percentage',
                'total_investment',
                'planned_investment',
                'planned_percentage',
                'actual_investment',
                'actual_percentage',
            ])
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

    public function ledgerBalance(?int $projectId = null, ?int $landParcelId = null): float
    {
        $creditQuery = $this->ledgerEntries();
        $debitQuery = $this->ledgerEntries();

        if ($projectId !== null) {
            $creditQuery->where('project_id', $projectId);
            $debitQuery->where('project_id', $projectId);
        }
        if ($landParcelId !== null) {
            $creditQuery->where('land_parcel_id', $landParcelId);
            $debitQuery->where('land_parcel_id', $landParcelId);
        }

        $credit = (float) $creditQuery->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT)->sum('amount');
        $debit = (float) $debitQuery->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT)->sum('amount');

        return round($credit - $debit, 2);
    }

    public function capitalDepositsTotal(?int $projectId = null, ?int $landParcelId = null): float
    {
        $query = $this->ledgerEntries()->where('type', ShareholderLedgerEntry::TYPE_CAPITAL);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        if ($landParcelId !== null) {
            $query->where('land_parcel_id', $landParcelId);
        }

        return round((float) $query->sum('amount'), 2);
    }
}
