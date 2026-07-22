<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $includeBrowsing = $request->boolean('include_browsing');

        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        if (! $includeBrowsing) {
            $query->where(function ($q): void {
                $q->where('log_name', '!=', 'http')
                    ->orWhereNull('log_name')
                    ->orWhere(function ($http): void {
                        $http->where('log_name', 'http')
                            ->where(function ($method): void {
                                $method->whereIn('event', ['POST', 'PUT', 'PATCH', 'DELETE'])
                                    ->orWhereIn('properties->method', ['POST', 'PUT', 'PATCH', 'DELETE']);
                            });
                    });
            });
        }

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

        if ($request->filled('causer_id')) {
            $query->where('causer_type', User::class)
                ->where('causer_id', (int) $request->input('causer_id'));
        }

        if ($request->filled('date_from')) {
            try {
                $from = Carbon::parse((string) $request->input('date_from'))->startOfDay();
                $query->where('created_at', '>=', $from);
            } catch (\Throwable) {
                //
            }
        }

        if ($request->filled('date_to')) {
            try {
                $to = Carbon::parse((string) $request->input('date_to'))->endOfDay();
                $query->where('created_at', '<=', $to);
            } catch (\Throwable) {
                //
            }
        }

        $activities = $query->paginate(50)->withQueryString();

        $presented = $activities->getCollection()->map(
            fn (Activity $row) => [
                'row' => $row,
                'view' => $this->presenter->present($row),
            ]
        );
        $activities->setCollection($presented);

        $logNames = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter()
            ->values();

        $causerIds = Activity::query()
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $users = $causerIds === []
            ? collect()
            : User::query()
                ->whereIn('id', $causerIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

        $todayStart = Carbon::now()->startOfDay();
        $stats = [
            'today_total' => Activity::query()->where('created_at', '>=', $todayStart)->count(),
            'today_actions' => Activity::query()
                ->where('created_at', '>=', $todayStart)
                ->where(function ($q): void {
                    $q->where('log_name', '!=', 'http')
                        ->orWhereNull('log_name')
                        ->orWhere(function ($http): void {
                            $http->where('log_name', 'http')
                                ->where(function ($method): void {
                                    $method->whereIn('event', ['POST', 'PUT', 'PATCH', 'DELETE'])
                                        ->orWhereIn('properties->method', ['POST', 'PUT', 'PATCH', 'DELETE']);
                                });
                        });
                })
                ->count(),
            'today_auth' => Activity::query()->where('created_at', '>=', $todayStart)->where('log_name', 'auth')->count(),
        ];

        return view('activity-log.index', [
            'title' => 'سجل النشاط | Mohaseb Aqary',
            'pageTitle' => 'سجل النشاط',
            'activities' => $activities,
            'logNames' => $logNames,
            'logNameLabels' => $this->presenter->logNameOptions(),
            'users' => $users,
            'q' => $request->string('q')->toString(),
            'filterLogName' => $request->string('log_name')->toString(),
            'filterCauserId' => $request->input('causer_id'),
            'dateFrom' => $request->string('date_from')->toString(),
            'dateTo' => $request->string('date_to')->toString(),
            'includeBrowsing' => $includeBrowsing,
            'stats' => $stats,
            'presenter' => $this->presenter,
        ]);
    }
}
