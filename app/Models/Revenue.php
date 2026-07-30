<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenue extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'amount',
        'category',
        'client_id',
        'contract_id',
        'sale_id',
        'source',
        'paid_at',
        'payment_method',
        'received_by_shareholder_id',
        'notes',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:5',
        'paid_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function receivedByShareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class, 'received_by_shareholder_id');
    }
}
