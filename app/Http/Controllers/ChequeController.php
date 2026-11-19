<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Revenue;
use App\Services\CashboxLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * سجل الشيكات الواردة من العملاء: في الخزنة → اتودع → اتصرف / مرتد.
 * الشيك بيدخل الصندوق ويقلل المتبقي على العميل لما يتصرف بس.
 */
class ChequeController extends Controller
{
    public function __construct(private readonly CashboxLedgerService $cashboxLedger) {}

    public function index(Project $project, Request $request): View
    {
        $status = (string) $request->query('status', 'open');
        $base = Revenue::query()->where('payment_method', 'check')->whereNotNull('cheque_status')
            ->where('approval_status', '!=', 'rejected');

        $q = (clone $base)->with(['client:id,name,phone', 'sale:id,property_id,floor_number,unit_number,is_mezzanine', 'sale.property:id,name']);
        match ($status) {
            'open' => $q->whereIn('cheque_status', [Revenue::CHEQUE_IN_HAND, Revenue::CHEQUE_DEPOSITED]),
            'due' => $q->whereIn('cheque_status', [Revenue::CHEQUE_IN_HAND, Revenue::CHEQUE_DEPOSITED])->whereDate('cheque_due_date', '<=', now()->addDays(7)),
            'cleared' => $q->where('cheque_status', Revenue::CHEQUE_CLEARED),
            'bounced' => $q->where('cheque_status', Revenue::CHEQUE_BOUNCED),
            default => null,
        };
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $q->where(fn ($w) => $w->where('cheque_number', 'like', $like)->orWhere('cheque_bank', 'like', $like)
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like)));
        }

        $sum = fn (array $statuses) => (float) (clone $base)->whereIn('cheque_status', $statuses)->sum('amount');
        $kpis = [
            'in_hand' => $sum([Revenue::CHEQUE_IN_HAND]),
            'deposited' => $sum([Revenue::CHEQUE_DEPOSITED]),
            'due_week' => (float) (clone $base)->whereIn('cheque_status', [Revenue::CHEQUE_IN_HAND, Revenue::CHEQUE_DEPOSITED])
                ->whereDate('cheque_due_date', '<=', now()->addDays(7))->sum('amount'),
            'bounced' => $sum([Revenue::CHEQUE_BOUNCED]),
        ];

        return view('cheques.index', [
            'title' => 'الشيكات | Mohaseb Aqary',
            'pageTitle' => 'سجل الشيكات',
            'project' => $project,
            'cheques' => $q->orderBy('cheque_due_date')->orderBy('id')->paginate(25)->withQueryString(),
            'status' => $status,
            'kpis' => $kpis,
        ]);
    }

    public function updateStatus(Project $project, Revenue $revenue, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cheque_status' => ['required', 'in:in_hand,deposited,cleared,bounced'],
        ]);
        if ($revenue->payment_method !== 'check') {
            return back()->with('error', 'التحصيل ده مش شيك.');
        }
        $new = $data['cheque_status'];
        $old = (string) $revenue->cheque_status;
        if ($new === $old) {
            return back();
        }
        $user = $request->user();
        $isPrivileged = $user instanceof \App\Models\User && ($user->isAdmin() || $user->role === 'accountant');
        if (in_array($new, [Revenue::CHEQUE_CLEARED, Revenue::CHEQUE_BOUNCED], true) && ! $isPrivileged) {
            return back()->with('error', 'تعليم الشيك «اتصرف» أو «مرتد» للأدمن أو المحاسب بس.');
        }
        if ($old === Revenue::CHEQUE_CLEARED && $new === Revenue::CHEQUE_BOUNCED) {
            // شيك كان اتحسب ورجع: لو الفلوس اتصرفت من الصندوق، الرصيد هيبقى بالسالب — بننبه بس، لأن ده اللي حصل فعلًا
            $after = app(\App\Services\CashboxBalanceService::class)->approvedBalance(
                (int) $revenue->project_id,
                ['reference_type' => Revenue::class, 'reference_id' => (int) $revenue->id]
            );
            if ($after < -0.00001 && ! ($user instanceof \App\Models\User && $user->isAdmin())) {
                return back()->with('error', 'ارتداد الشيك هيخلّي الصندوق بالسالب؛ لازم الأدمن هو اللي يعلّمه.');
            }
        }

        DB::transaction(function () use ($revenue, $new): void {
            $revenue->update(['cheque_status' => $new, 'cheque_status_at' => now()]);
            $this->cashboxLedger->syncFromRevenue($revenue->refresh());
            $this->recalculateContract((int) $revenue->contract_id);
        });

        activity()->performedOn($revenue)->causedBy($user)
            ->withProperties(['from' => $old, 'to' => $new])
            ->log('cheque_status');

        $msg = match ($new) {
            Revenue::CHEQUE_CLEARED => 'الشيك اتصرف ودخل الصندوق، والمتبقي على العميل اتحدّث.',
            Revenue::CHEQUE_BOUNCED => 'الشيك اتعلّم مرتد. المبلغ لسه على العميل.',
            Revenue::CHEQUE_DEPOSITED => 'الشيك اتعلّم إنه اتودع البنك.',
            default => 'اتحدثت حالة الشيك.',
        };

        return back()->with('success', $msg);
    }

    private function recalculateContract(int $contractId): void
    {
        app(\App\Services\ContractBalanceService::class)->recalculate($contractId);
    }
}
