<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TreasuryTransaction;
use App\Support\LandTradingCashbox;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LandCashboxController extends Controller
{
    public function index(Request $request): View
    {
        $project = LandTradingCashbox::project();
        $projectId = (int) $project->id;
        $filters = ListingFilters::fromRequest($request);

        $base = TreasuryTransaction::withoutProjectScope()->where('project_id', $projectId);

        $approvedInQuery = (clone $base)->where('type', 'revenue')->where('approval_status', 'approved');
        $approvedOutQuery = (clone $base)->where('type', 'expense')->where('approval_status', 'approved');
        $pendingInQuery = (clone $base)->where('type', 'revenue')->where('approval_status', 'pending');
        $pendingOutQuery = (clone $base)->where('type', 'expense')->where('approval_status', 'pending');

        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $approvedInQuery->where('description', 'like', $like);
            $approvedOutQuery->where('description', 'like', $like);
            $pendingInQuery->where('description', 'like', $like);
            $pendingOutQuery->where('description', 'like', $like);
        }
        $filters->applyWhereDate($approvedInQuery, 'created_at');
        $filters->applyWhereDate($approvedOutQuery, 'created_at');
        $filters->applyWhereDate($pendingInQuery, 'created_at');
        $filters->applyWhereDate($pendingOutQuery, 'created_at');

        $treasuryIn = (float) (clone $approvedInQuery)->sum('amount');
        $treasuryOut = (float) (clone $approvedOutQuery)->sum('amount');
        $pendingIn = (float) (clone $pendingInQuery)->sum('amount');
        $pendingOut = (float) (clone $pendingOutQuery)->sum('amount');

        $txQuery = TreasuryTransaction::withoutProjectScope()
            ->where('project_id', $projectId)
            ->latest();
        if ($filters->q !== '') {
            $txQuery->where('description', 'like', '%'.$filters->likeTerm().'%');
        }
        $filters->applyWhereDate($txQuery, 'created_at');

        $currency = Setting::withoutProjectScope()->value('currency') ?? 'EGP';

        return view('cashbox.land', [
            'title' => 'صندوق الأراضي | Mohaseb Aqary',
            'pageTitle' => 'صندوق الأراضي',
            'project' => $project,
            'currency' => $currency,
            'openingBalance' => 0.0,
            'revenuesTotal' => $treasuryIn,
            'expensesTotal' => $treasuryOut,
            'pendingIn' => $pendingIn,
            'pendingOut' => $pendingOut,
            'currentBalance' => round($treasuryIn - $treasuryOut, 2),
            'transactions' => $txQuery->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:revenue,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $isAdmin = $user instanceof \App\Models\User && $user->isAdmin();
        $projectId = LandTradingCashbox::projectId();

        TreasuryTransaction::withoutProjectScope()->create([
            'project_id' => $projectId,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? 'حركة يدوية — صندوق الأراضي',
            'approval_status' => $isAdmin ? 'approved' : 'pending',
            'approved_at' => $isAdmin ? now() : null,
            'approved_by' => $isAdmin ? (int) $user->id : null,
        ]);

        return redirect()
            ->route('land-cashbox.index')
            ->with('success', $isAdmin ? 'تم تسجيل الحركة واعتمادها.' : 'تم تسجيل الحركة كمعلقة حتى اعتماد الأدمن.');
    }
}
