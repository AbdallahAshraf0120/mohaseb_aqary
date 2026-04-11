<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'sale_id',
        'client_id',
        'property_id',
        'start_date',
        'end_date',
        'total_price',
        'paid_amount',
        'remaining_amount',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
