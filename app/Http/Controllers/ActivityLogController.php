<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('description', 'like', $term)
                    ->orWhere('log_name', 'like', $term)
                    ->orWhere('event', 'like', $term)
                    ->orWhere('properties->route', 'like', $term)
                    ->orWhere('properties->path', 'like', $term)
                    ->orWhere('properties->method', 'like', $term)
                    ->orWhere('properties', 'like', $term);
            });
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->string('log_name'));
        }

        $activities = $query->paginate(75)->withQueryString();

        $logNames = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter()
            ->values();

        $todayStart = Carbon::now()->startOfDay();
        $stats = [
            'today_total' => Activity::query()->where('created_at', '>=', $todayStart)->count(),
            'today_http' => Activity::query()->where('created_at', '>=', $todayStart)->where('log_name', 'http')->count(),
            'today_auth' => Activity::query()->where('created_at', '>=', $todayStart)->where('log_name', 'auth')->count(),
        ];

        return view('activity-log.index', [
            'title' => 'سجل النشاط | Mohaseb Aqary',
            'pageTitle' => 'سجل النشاط',
            'activities' => $activities,
            'logNames' => $logNames,
            'q' => $request->string('q')->toString(),
            'filterLogName' => $request->string('log_name')->toString(),
            'stats' => $stats,
        ]);
    }
}
