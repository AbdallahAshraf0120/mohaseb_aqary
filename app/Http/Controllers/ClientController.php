<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Client::query()->withCount('sales');
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('national_id', 'like', $like);
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $clientIds = (clone $query)->pluck('id');
        $clientKpis = [
            'count' => $clientIds->count(),
            'with_sales' => $clientIds->isEmpty()
                ? 0
                : (int) Client::query()->whereKey($clientIds)->whereHas('sales')->count(),
            'sales_ops' => $clientIds->isEmpty()
                ? 0
                : (int) Sale::query()->whereIn('client_id', $clientIds)->count(),
        ];

        return view('clients.index', [
            'title' => 'العملاء | Mohaseb Aqary',
            'pageTitle' => 'العملاء',
            'project' => $project,
            'clientKpis' => $clientKpis,
            'clients' => $query->latest()->paginate(15)->withQueryString(),
            'modules' => $this->modules(),
        ]);
    }

    public function show(Project $project, Client $client): View
    {
        $client->load([
            'sales' => function ($query): void {
                $query->orderByDesc('sale_date')
                    ->orderByDesc('id')
                    ->with([
                        'property:id,name,mezzanine_floors',
                        'contract.revenues' => function ($q): void {
                            $q->orderBy('paid_at')->orderBy('id');
                        },
                    ]);
            },
        ]);

        $sales = $client->sales;
        $stats = [
            'sales_count' => $sales->count(),
            'total_sale_price' => round((float) $sales->sum(static fn (Sale $s) => (float) $s->sale_price), 5),
            'total_down_payment' => round((float) $sales->sum(static fn (Sale $s) => (float) $s->down_payment), 5),
            'total_remaining_contracts' => round((float) $sales->where('approval_status', 'approved')->sum(static fn (Sale $s) => (float) ($s->contract?->remaining_amount ?? 0)), 5),
            'total_collected_revenues' => round((float) Revenue::query()->where('client_id', $client->id)->counted()->sum('amount'), 5),
        ];

        return view('clients.show', [
            'title' => 'بروفايل العميل | Mohaseb Aqary',
            'pageTitle' => 'بروفايل العميل',
            'project' => $project,
            'client' => $client,
            'stats' => $stats,
            'modules' => $this->modules(),
        ]);
    }

    /**
     * كشف حساب العميل: كل وحداته، جدول أقساطها، والمدفوعات بترتيب التاريخ مع رصيد جاري.
     */
    public function statement(Project $project, Client $client): View
    {
        $sales = Sale::query()
            ->where('client_id', $client->id)
            ->whereNotIn('approval_status', ['rejected'])
            ->with([
                'property:id,name',
                'contract.revenues' => fn ($q) => $q->where('approval_status', 'approved')->orderBy('paid_at')->orderBy('id'),
            ])
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get();

        $today = now()->startOfDay();
        $units = [];
        $ledger = [];
        $cheques = [];
        foreach ($sales as $sale) {
            $approved = $sale->approval_status === 'approved';
            $schedule = $approved ? $sale->installmentScheduleWithPaymentSummary() : [];
            $collected = (float) ($sale->contract?->revenues?->filter(fn (Revenue $r) => $r->isCounted())->sum('amount') ?? 0);
            $paid = (float) $sale->down_payment + $collected;
            $units[] = [
                'sale' => $sale,
                'approved' => $approved,
                'schedule' => $schedule,
                'price' => (float) $sale->sale_price,
                'paid' => $paid,
                'remaining' => max(0.0, (float) $sale->sale_price - $paid),
                'overdue' => (float) collect($schedule)->filter(fn ($r) => $r['due_date']->lt($today))->sum('balance'),
            ];
            if (! $approved) {
                continue;
            }
            $ledger[] = ['date' => $sale->sale_date, 'desc' => 'تعاقد '.($sale->property?->name ?? '').' — '.$sale->unitLabel(), 'debit' => (float) $sale->sale_price, 'credit' => 0.0, 'ref' => 'SL-'.$sale->id];
            if ((float) $sale->down_payment > 0) {
                $ledger[] = ['date' => $sale->sale_date, 'desc' => 'مقدم تعاقد', 'debit' => 0.0, 'credit' => (float) $sale->down_payment, 'ref' => $sale->downPaymentReceiptNumber()];
            }
            foreach ($sale->contract?->revenues ?? [] as $rev) {
                if (! $rev->isCounted()) {
                    $cheques[] = $rev;
                    continue;
                }
                $ledger[] = [
                    'date' => $rev->paid_at,
                    'desc' => ($rev->category ?: 'تحصيل').' — '.($sale->property?->name ?? ''),
                    'debit' => 0.0,
                    'credit' => (float) $rev->amount,
                    'ref' => $rev->receiptNumber(),
                ];
            }
        }
        usort($ledger, fn ($a, $b) => [optional($a['date'])->timestamp ?? 0, $a['debit'] > 0 ? 0 : 1] <=> [optional($b['date'])->timestamp ?? 0, $b['debit'] > 0 ? 0 : 1]);
        $running = 0.0;
        foreach ($ledger as $i => $row) {
            $running += $row['debit'] - $row['credit'];
            $ledger[$i]['balance'] = $running;
        }

        $approvedUnits = collect($units)->where('approved', true);

        return view('clients.statement', [
            'title' => 'كشف حساب '.$client->name.' | Mohaseb Aqary',
            'pageTitle' => 'كشف حساب العميل',
            'project' => $project,
            'client' => $client,
            'units' => $units,
            'ledger' => $ledger,
            'cheques' => $cheques,
            'totals' => [
                'price' => (float) $approvedUnits->sum('price'),
                'paid' => (float) $approvedUnits->sum('paid'),
                'remaining' => (float) $approvedUnits->sum('remaining'),
                'overdue' => (float) $approvedUnits->sum('overdue'),
            ],
            'companyName' => \App\Models\Setting::query()->value('company_name') ?: $project->name,
            'modules' => $this->modules(),
        ]);
    }

    public function duplicates(Project $project, \App\Services\ClientDirectory $directory): View
    {
        return view('clients.duplicates', [
            'title' => 'العملاء المكررين | Mohaseb Aqary',
            'pageTitle' => 'العملاء المكررين',
            'project' => $project,
            'groups' => $directory->duplicateGroups(),
        ]);
    }

    public function merge(Project $project, \Illuminate\Http\Request $request, \App\Services\ClientDirectory $directory): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if (! ($user instanceof \App\Models\User && $user->isAdmin())) {
            return back()->with('error', 'دمج العملاء للأدمن بس.');
        }
        $data = $request->validate([
            'keep_id' => ['required', 'integer'],
            'ids' => ['required', 'array', 'min:2'],
            'ids.*' => ['integer'],
        ]);
        $clients = Client::query()->whereIn('id', $data['ids'])->get();
        $keep = $clients->firstWhere('id', (int) $data['keep_id']);
        if (! $keep || $clients->count() < 2) {
            return back()->with('error', 'اختيار غير صحيح.');
        }
        $nids = $clients->map(fn (Client $c) => \App\Services\ClientDirectory::normalizeNationalId($c->national_id))->filter()->unique();
        if ($nids->count() > 1) {
            return back()->with('error', 'مش هينفع الدمج: العملاء دول ليهم أرقام قومية مختلفة.');
        }
        $n = $directory->merge($keep, $clients);
        activity()->performedOn($keep)->causedBy($user)->withProperties(['merged_ids' => $clients->pluck('id')->all()])->log('clients_merged');

        return redirect()->route('clients.duplicates', $project)->with('success', "اتدمج {$n} عميل مكرر في «{$keep->name}».");
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
