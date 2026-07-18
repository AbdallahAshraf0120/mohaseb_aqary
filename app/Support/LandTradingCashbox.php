<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Schema;

class LandTradingCashbox
{
    public static function project(): Project
    {
        if (! Schema::hasColumn('projects', 'is_land_trading_cashbox')) {
            abort(503, 'قاعدة البيانات غير محدّثة. شغّل: php artisan migrate --force');
        }

        $project = Project::query()->where('is_land_trading_cashbox', true)->first();
        if ($project instanceof Project) {
            return $project;
        }

        return Project::query()->create([
            'name' => 'أراضي البيع والشراء',
            'code' => 'LAND-TRADING',
            'capital' => 0,
            'is_active' => true,
            'is_draft' => false,
            'is_land_trading_cashbox' => true,
        ]);
    }

    public static function projectId(): int
    {
        return (int) self::project()->id;
    }
}
