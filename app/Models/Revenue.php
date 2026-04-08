<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    protected $fillable = [
        'amount',
        'category',
        'client_id',
        'contract_id',
        'sale_id',
        'source',
        'paid_at',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'paid_at' => 'date',
    ];

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
}
