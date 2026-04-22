<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'client_id',
        'property_id',
        'floor_number',
        'is_mezzanine',
        'apartment_model',
        'sale_price',
        'payment_type',
        'down_payment',
        'installment_months',
        'installment_start_date',
        'installment_plan',
        'sale_date',
        'broker_name',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'installment_start_date' => 'date',
        'installment_plan' => 'array',
        'is_mezzanine' => 'boolean',
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

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }
}
