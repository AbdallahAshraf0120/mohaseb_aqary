<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'sale_id',
        'client_id',
        'property_id',
        'start_date',
        'end_date',
        'total_price',
        'paid_amount',
        'remaining_amount',
    ];

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
