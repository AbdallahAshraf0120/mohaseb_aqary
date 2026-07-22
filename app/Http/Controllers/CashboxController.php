<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Setting;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\TreasuryTransaction;
use App\Services\CashboxBalanceService;
use App\Services\ShareholderLedgerService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CashboxController extends Controller
{
    public function __construct(
        private readonly CashboxBalanceService $cashboxBalanceService,
        private readonly ShareholderLedgerService $shareholderLedgerService,
    ) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $opening = 0.0;

        $approvedInQuery = TreasuryTransaction::query()->where('type', 'revenue')->where('approval_status', 'approved');
        $approvedOutQuery = TreasuryTransaction::query()->where('type', 'expense')->where('approval_status', 'approved');
        $pendingInQuery = TreasuryTransaction::query()->where('type', 'revenue')->where('approval_status', 'pending');
        $pendingOutQuery = TreasuryTransaction::query()->where('type', 'expense')->where('approval_status', 'pending');
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
        $currentBalance = $opening + $treasuryIn - $treasuryOut;

        $txQuery = TreasuryTransaction::query()->latest();
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $txQuery->where('description', 'like', $like);
        }
        $filters->applyWhereDate($txQuery, 'created_at');

        $setting = Setting::query()->first();
        $currency = $setting?->currency ?? 'EGP';

        $projectShareholders = ProjectShareholder::query()
            ->where('project_id', (int) $project->id)
            ->with('shareholder:id,name')
            ->orderBy('shareholder_id')
            ->get();

        return view('cashbox.index', [
            'title' => 'الصندوق | Mohaseb Aqary',
            'pageTitle' => 'الصندوق',
            'project' => $project,
            'currency' => $currency,
            'openingBalance' => $opening,
            'revenuesTotal' => $treasuryIn,
            'expensesTotal' => $treasuryOut,
            'pendingIn' => $pendingIn,
            'pendingOut' => $pendingOut,
            'currentBalance' => $currentBalance,
            'transactions' => $txQuery->paginate(15)->withQueryString(),
            'projectShareholders' => $projectShareholders,
            'modules' => $this->modules(),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:revenue,expense,shareholder_payout'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
            'shareholder_id' => ['nullable', 'integer', 'exists:shareholders,id', 'required_if:type,shareholder_payout'],
        ], [
            'shareholder_id.required_if' => 'اختر المساهم المستفيد من الصرف.',
        ]);

        $user = $request->user();
        $isAdmin = $user instanceof \App\Models\User && $user->isAdmin();
        $amount = round((float) $data['amount'], 2);

        if (in_array($data['type'], ['expense', 'shareholder_payout'], true)) {
            try {
                $this->cashboxBalanceService->assertCanSpend((int) $project->id, $amount);
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        if ($data['type'] === 'shareholder_payout') {
            $shareholder = Shareholder::query()->find((int) $data['shareholder_id']);
            if (! $shareholder instanceof Shareholder) {
                return back()->withInput()->with('error', 'المساهم غير موجود.');
            }

            $member = ProjectShareholder::query()
                ->where('project_id', (int) $project->id)
                ->where('shareholder_id', (int) $shareholder->id)
                ->exists();
            if (! $member) {
                return back()->withInput()->with('error', 'المساهم غير مرتبط بهذا المشروع.');
            }

            $note = trim((string) ($data['description'] ?? ''));
            if ($note === '') {
                $note = 'صرف من صندوق المشروع للمساهم';
            }

            try {
                $this->shareholderLedgerService->create($shareholder, [
                    'project_id' => (int) $project->id,
                    'type' => ShareholderLedgerEntry::TYPE_WITHDRAWAL,
                    'amount' => $amount,
                    'entry_date' => now()->toDateString(),
                    'notes' => $note,
                ], $user instanceof \App\Models\User ? $user : null);
            } catch (InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()
                ->route('cashbox.index', [$project])
                ->with(
                    'success',
                    $isAdmin
                        ? 'تم صرف '.$amount.' ج.م للمساهم «'.$shareholder->name.'» وتسجيلها في الجاري والصندوق.'
                        : 'تم تسجيل صرف للمساهم «'.$shareholder->name.'» في الجاري والصندوق (معلّق حتى اعتماد الأدمن).'
                );
        }

        TreasuryTransaction::query()->create([
            'type' => $data['type'],
            'amount' => $amount,
            'description' => $data['description'] ?? null,
            'approval_status' => $isAdmin ? 'approved' : 'pending',
            'approved_at' => $isAdmin ? now() : null,
            'approved_by' => $isAdmin ? (int) $user->id : null,
        ]);

        return redirect()
            ->route('cashbox.index', [$project])
            ->with('success', $isAdmin ? 'تم تسجيل حركة الصندوق واعتمادها تلقائيًا.' : 'تم تسجيل حركة الصندوق كعملية معلقة حتى اعتماد الأدمن.');
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
