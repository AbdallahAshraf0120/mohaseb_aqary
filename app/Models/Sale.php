<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'client_id',
        'property_id',
        'floor_number',
        'apartment_model',
        'sale_price',
        'payment_type',
        'down_payment',
        'installment_months',
        'installment_start_date',
        'installment_plan',
        'sale_date',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'installment_start_date' => 'date',
        'installment_plan' => 'array',
    ];

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
