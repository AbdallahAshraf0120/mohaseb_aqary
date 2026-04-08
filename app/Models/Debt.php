<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $fillable = ['client_id', 'total_amount', 'paid_amount', 'remaining_amount', 'status'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
