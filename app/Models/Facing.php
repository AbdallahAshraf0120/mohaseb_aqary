<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facing extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'code',
        'name',
        'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function seedDefaultsForProject(int $projectId): void
    {
        $defaults = [
            ['code' => 'normal', 'name' => 'عادية', 'sort_order' => 0],
            ['code' => 'facade', 'name' => 'واجهة', 'sort_order' => 10],
            ['code' => 'corner', 'name' => 'ناصية', 'sort_order' => 20],
        ];
        foreach ($defaults as $row) {
            static::query()->firstOrCreate(
                [
                    'project_id' => $projectId,
                    'code' => $row['code'],
                ],
                [
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
