<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskUpdate extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'note',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'happened_at',
    ];

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

