<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'amount',
        'category',
        'description',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
