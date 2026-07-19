<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseType extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public static function seedDefaultsForProject(int $projectId): void
    {
        $defaults = [
            ['name' => 'مقاولات', 'sort_order' => 10],
            ['name' => 'كهرباء', 'sort_order' => 20],
            ['name' => 'مياه', 'sort_order' => 30],
            ['name' => 'صيانة', 'sort_order' => 40],
            ['name' => 'رواتب', 'sort_order' => 50],
            ['name' => 'تسويق', 'sort_order' => 60],
            ['name' => 'مصروفات إدارية', 'sort_order' => 70],
            ['name' => 'أخرى', 'sort_order' => 100],
        ];

        foreach ($defaults as $row) {
            static::withoutGlobalScope('project')->firstOrCreate(
                [
                    'project_id' => $projectId,
                    'name' => $row['name'],
                ],
                [
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
