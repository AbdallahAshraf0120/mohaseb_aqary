<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['company_name', 'currency', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];
}
