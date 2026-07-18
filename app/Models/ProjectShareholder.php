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
    ];

    protected function casts(): array
    {
        return [
            'share_percentage' => 'decimal:2',
            'total_investment' => 'decimal:2',
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
