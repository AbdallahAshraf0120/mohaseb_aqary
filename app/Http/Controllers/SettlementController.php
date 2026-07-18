<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Revenue;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $revQ = Revenue::query()->where('approval_status', 'approved');
        $expQ = Expense::query()->where('approval_status', 'approved');
        $filters->applyWhereDate($revQ, 'paid_at');
        $filters->applyWhereDate($expQ, 'spent_at');
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $revQ->where(function ($w) use ($like): void {
                $w->where('notes', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $like));
            });
            $expQ->where(function ($w) use ($like): void {
                $w->where('category', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $revenues = (float) (clone $revQ)->sum('amount');
        $expenses = (float) (clone $expQ)->sum('amount');

        $shareholders = ProjectShareholder::query()
            ->with('shareholder:id,name')
            ->where('project_id', (int) $project->id)
            ->get()
            ->filter(fn (ProjectShareholder $m) => $m->shareholder !== null)
            ->map(function (ProjectShareholder $m) use ($project) {
                /** @var Shareholder $sh */
                $sh = $m->shareholder;
                $credit = (float) $sh->ledgerEntries()
                    ->where('project_id', (int) $project->id)
                    ->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT)
                    ->sum('amount');
                $debit = (float) $sh->ledgerEntries()
                    ->where('project_id', (int) $project->id)
                    ->where('direction', ShareholderLedgerEntry::DIRECTION_DEBIT)
                    ->sum('amount');
                $capital = (float) $sh->ledgerEntries()
                    ->where('project_id', (int) $project->id)
                    ->where('type', ShareholderLedgerEntry::TYPE_CAPITAL)
                    ->sum('amount');

                $sh->setAttribute('ledger_balance', round($credit - $debit, 2));
                $sh->setAttribute('capital_deposits_total', round($capital, 2));
                $sh->setAttribute('share_percentage', (float) $m->share_percentage);

                return $sh;
            })
            ->sortBy('name', SORT_NATURAL)
            ->values();

        return view('settlements.index', [
            'title' => 'التسويات | Mohaseb Aqary',
            'pageTitle' => 'التسويات',
            'project' => $project,
            'revenues' => $revenues,
            'expenses' => $expenses,
            'net' => $revenues - $expenses,
            'shareholders' => $shareholders,
            'shareholderLedgerTotal' => (float) $shareholders->sum(fn ($sh) => (float) ($sh->ledger_balance ?? 0)),
            'modules' => $this->modules(),
        ]);
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
            'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
            'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
            'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
            'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
