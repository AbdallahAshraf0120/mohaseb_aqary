<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'name', 'phone', 'email', 'national_id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
