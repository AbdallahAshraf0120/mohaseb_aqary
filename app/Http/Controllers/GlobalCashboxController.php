<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Setting;
use App\Models\TreasuryTransaction;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalCashboxController extends Controller
{
    public function index(Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $projectId = $request->filled('project_id') ? (int) $request->query('project_id') : null;
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));

        $baseQuery = TreasuryTransaction::withoutProjectScope()->with('project:id,name');
        if ($projectId) {
            $baseQuery->where('project_id', $projectId);
        }
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $baseQuery->where('description', 'like', $like);
        }
        $filters->applyWhereDate($baseQuery, 'created_at');
        if (in_array($type, ['revenue', 'expense'], true)) {
            $baseQuery->where('type', $type);
        }
        if (in_array($status, ['approved', 'pending', 'rejected'], true)) {
            $baseQuery->where('approval_status', $status);
        }

        $treasuryIn = (float) (clone $baseQuery)->where('type', 'revenue')->where('approval_status', 'approved')->sum('amount');
        $treasuryOut = (float) (clone $baseQuery)->where('type', 'expense')->where('approval_status', 'approved')->sum('amount');
        $pendingIn = (float) (clone $baseQuery)->where('type', 'revenue')->where('approval_status', 'pending')->sum('amount');
        $pendingOut = (float) (clone $baseQuery)->where('type', 'expense')->where('approval_status', 'pending')->sum('amount');
        $currentBalance = round($treasuryIn - $treasuryOut, 2);

        $projectTotalsQuery = TreasuryTransaction::withoutProjectScope()
            ->select([
                'project_id',
                DB::raw("COALESCE(SUM(CASE WHEN type = 'revenue' AND approval_status = 'approved' THEN amount ELSE 0 END), 0) as approved_in"),
                DB::raw("COALESCE(SUM(CASE WHEN type = 'expense' AND approval_status = 'approved' THEN amount ELSE 0 END), 0) as approved_out"),
                DB::raw("COALESCE(SUM(CASE WHEN type = 'revenue' AND approval_status = 'pending' THEN amount ELSE 0 END), 0) as pending_in"),
                DB::raw("COALESCE(SUM(CASE WHEN type = 'expense' AND approval_status = 'pending' THEN amount ELSE 0 END), 0) as pending_out"),
                DB::raw('COUNT(*) as movements_count'),
            ])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($filters->q !== '', function ($q) use ($filters): void {
                $q->where('description', 'like', '%'.$filters->likeTerm().'%');
            });
        $filters->applyWhereDate($projectTotalsQuery, 'created_at');

        $projectTotals = $projectTotalsQuery
            ->groupBy('project_id')
            ->get();

        $projectsById = Project::query()
            ->whereIn('id', $projectTotals->pluck('project_id')->filter()->unique())
            ->get(['id', 'name'])
            ->keyBy('id');

        $byProject = $projectTotals
            ->map(function ($row) use ($projectsById) {
                $in = (float) $row->approved_in;
                $out = (float) $row->approved_out;

                return (object) [
                    'project_id' => (int) $row->project_id,
                    'project_name' => $projectsById->get((int) $row->project_id)?->name ?? '—',
                    'approved_in' => $in,
                    'approved_out' => $out,
                    'pending_in' => (float) $row->pending_in,
                    'pending_out' => (float) $row->pending_out,
                    'movements_count' => (int) $row->movements_count,
                    'balance' => round($in - $out, 2),
                ];
            })
            ->sortByDesc('balance')
            ->values();

        $currency = Setting::withoutProjectScope()->value('currency') ?? 'EGP';

        return view('cashbox.global', [
            'title' => 'الصندوق الشامل | Mohaseb Aqary',
            'pageTitle' => 'الصندوق الشامل',
            'currency' => $currency,
            'revenuesTotal' => $treasuryIn,
            'expensesTotal' => $treasuryOut,
            'pendingIn' => $pendingIn,
            'pendingOut' => $pendingOut,
            'currentBalance' => $currentBalance,
            'transactions' => (clone $baseQuery)->latest()->paginate(20)->withQueryString(),
            'byProject' => $byProject,
            'projects' => Project::query()->listed()->orderBy('name')->get(['id', 'name']),
            'selectedProjectId' => $projectId,
            'selectedType' => $type,
            'selectedStatus' => $status,
        ]);
    }
}
