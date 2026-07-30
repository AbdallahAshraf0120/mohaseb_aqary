<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectShareholder extends Model
{
    protected $table = 'project_shareholder';

    protected $fillable = [
        'project_id',
        'shareholder_id',
        'share_percentage',
        'total_investment',
        'planned_investment',
        'planned_percentage',
        'actual_investment',
        'actual_percentage',
    ];

    protected function casts(): array
    {
        return [
            'share_percentage' => 'decimal:5',
            'total_investment' => 'decimal:5',
            'planned_investment' => 'decimal:5',
            'planned_percentage' => 'decimal:5',
            'actual_investment' => 'decimal:5',
            'actual_percentage' => 'decimal:5',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class);
    }
}
