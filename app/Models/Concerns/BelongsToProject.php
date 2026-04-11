<?php

namespace App\Models\Concerns;

use App\Support\CurrentProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToProject
{
    public static function bootBelongsToProject(): void
    {
        static::addGlobalScope('project', function (Builder $builder): void {
            $projectId = app(CurrentProject::class)->id();
            if ($projectId !== null) {
                $builder->where($builder->getModel()->getTable() . '.project_id', $projectId);
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('project_id') === null) {
                $pid = app(CurrentProject::class)->id();
                if ($pid !== null) {
                    $model->setAttribute('project_id', $pid);
                }
            }
        });
    }

    public function scopeWithoutProjectScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('project');
    }
}
