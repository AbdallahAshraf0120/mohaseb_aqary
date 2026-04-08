<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shareholder extends Model
{
    protected $fillable = ['name', 'share_percentage', 'total_investment', 'profit_amount'];
}
