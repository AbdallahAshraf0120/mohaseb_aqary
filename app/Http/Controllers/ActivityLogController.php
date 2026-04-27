<?php

namespace App\Http\Controllers;

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
                    ->orWhere('event', 'like', $term);
            });
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->string('log_name'));
        }

        $activities = $query->paginate(40)->withQueryString();

        $logNames = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter()
            ->values();

        return view('activity-log.index', [
            'title' => 'سجل النشاط | Mohaseb Aqary',
            'pageTitle' => 'سجل النشاط',
            'activities' => $activities,
            'logNames' => $logNames,
            'q' => $request->string('q')->toString(),
            'filterLogName' => $request->string('log_name')->toString(),
        ]);
    }
}
