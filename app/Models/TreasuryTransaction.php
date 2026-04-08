<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryTransaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
    ];
}
