<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shareholder extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'name', 'share_percentage', 'total_investment', 'profit_amount'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
