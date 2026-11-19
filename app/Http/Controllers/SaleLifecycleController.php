<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\SaleTransfer;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\CashboxBalanceService;
use App\Services\ClientDirectory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * بعد التعاقد: إلغاء التعاقد (مع استرداد كلي/جزئي) والتنازل لعميل تاني.
 */
class SaleLifecycleController extends Controller
{
    /** إجمالي اللي العميل دفعه فعلًا على البيعة (مقدم + تحصيلات محسوبة). */
    private function paidOnSale(Sale $sale): float
    {
        $collected = $sale->contract ? (float) Revenue::query()->where('contract_id', $sale->contract->id)->counted()->sum('amount') : 0.0;

        return round((float) $sale->down_payment + $collected, 5);
    }

    public function cancelForm(Project $project, Sale $sale): View|RedirectResponse
    {
        if ($sale->approval_status !== 'approved') {
            return redirect()->route('sales.show', [$project, $sale])->with('error', 'الإلغاء للبيعات المعتمدة بس.');
        }
        $sale->load(['client', 'property', 'contract']);

        return view('sales.cancel', [
            'title' => 'إلغاء تعاقد | Mohaseb Aqary',
            'pageTitle' => 'إلغاء التعاقد واسترداد الفلوس',
            'project' => $project,
            'sale' => $sale,
            'paid' => $this->paidOnSale($sale),
            'balance' => app(CashboxBalanceService::class)->approvedBalance((int) $project->id),
        ]);
    }

    public function cancel(Project $project, Sale $sale, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! ($user instanceof User && $user->isAdmin())) {
            return back()->with('error', 'إلغاء التعاقد للأدمن بس.');
        }
        if ($sale->approval_status !== 'approved') {
            return back()->with('error', 'الإلغاء للبيعات المعتمدة بس.');
        }
        $sale->load('contract');
        $paid = $this->paidOnSale($sale);
        $data = $request->validate([
            'refund_amount' => ['required', 'numeric', 'min:0', 'max:'.max(0, $paid)],
            'reason' => ['required', 'string', 'max:500'],
        ], ['refund_amount.max' => 'المبلغ المسترد ما ينفعش يزيد عن اللي العميل دفعه ('.\App\Support\Money::display($paid).').']);
        $refund = round((float) $data['refund_amount'], 5);

        if ($sale->contract && Revenue::query()->where('contract_id', $sale->contract->id)->where('approval_status', '!=', 'rejected')->unclearedCheques()->exists()) {
            return back()->withInput()->with('error', 'فيه شيكات على العقد لسه ما اتصرفتش. علّمها «مرتد» أو امسحها من صفحة الشيكات قبل الإلغاء.');
        }
        if ($sale->contract && Revenue::query()->where('contract_id', $sale->contract->id)->where('approval_status', 'pending')->exists()) {
            return back()->withInput()->with('error', 'فيه تحصيلات معلّقة على العقد؛ اعتمدها أو ارفضها الأول.');
        }
        if ($refund > 0.00001) {
            try {
                app(CashboxBalanceService::class)->assertCanSpend((int) $project->id, $refund);
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', 'رصيد الصندوق مش كفاية للاسترداد: '.$e->getMessage());
            }
        }

        DB::transaction(function () use ($sale, $refund, $data, $user, $project): void {
            $sale->update([
                'approval_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $data['reason'],
                'refund_amount' => $refund,
            ]);
            if ($refund > 0.00001) {
                TreasuryTransaction::query()->create([
                    'project_id' => $project->id,
                    'type' => 'expense',
                    'amount' => $refund,
                    'reference_type' => 'sale_refund',
                    'reference_id' => $sale->id,
                    'description' => 'استرداد لعميل — إلغاء تعاقد بيعة SL-'.$sale->id,
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                ]);
            }
        });
        activity()->performedOn($sale)->causedBy($user)->withProperties(['refund' => $refund, 'paid' => $paid, 'reason' => $data['reason']])->log('sale_cancelled');

        return redirect()->route('sales.show', [$project, $sale])->with('success', 'اتلغى التعاقد والوحدة بقت متاحة. المسترد '.\App\Support\Money::display($refund).' ج.م، والمحتجز '.\App\Support\Money::display($paid - $refund).' ج.م.');
    }

    public function transferForm(Project $project, Sale $sale): View|RedirectResponse
    {
        if ($sale->approval_status !== 'approved') {
            return redirect()->route('sales.show', [$project, $sale])->with('error', 'التنازل للبيعات المعتمدة بس.');
        }
        $sale->load(['client', 'property', 'transfers.fromClient', 'transfers.toClient']);

        return view('sales.transfer', [
            'title' => 'تنازل عن وحدة | Mohaseb Aqary',
            'pageTitle' => 'تنازل عن الوحدة لعميل تاني',
            'project' => $project,
            'sale' => $sale,
            'existingClients' => Client::query()->whereKeyNot($sale->client_id)->orderBy('name')->get(['id', 'name', 'phone', 'national_id']),
        ]);
    }

    public function transfer(Project $project, Sale $sale, Request $request, ClientDirectory $directory): RedirectResponse
    {
        $user = $request->user();
        if (! ($user instanceof User && $user->isAdmin())) {
            return back()->with('error', 'التنازل للأدمن بس.');
        }
        if ($sale->approval_status !== 'approved') {
            return back()->with('error', 'التنازل للبيعات المعتمدة بس.');
        }
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:30'],
            'client_national_id' => ['nullable', 'string', 'max:50'],
            'fee' => ['nullable', 'numeric', 'min:0', 'max:'.\App\Support\Money::MAX_AMOUNT],
            'transferred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $to = $directory->findOrCreate([
            'name' => $data['client_name'], 'phone' => $data['client_phone'], 'national_id' => $data['client_national_id'] ?? null,
        ]);
        if ((int) $to->id === (int) $sale->client_id) {
            return back()->withInput()->with('error', 'العميل الجديد هو نفس العميل الحالي.');
        }
        $fee = round((float) ($data['fee'] ?? 0), 5);
        $from = (int) $sale->client_id;

        DB::transaction(function () use ($sale, $to, $from, $fee, $data, $user, $project): void {
            SaleTransfer::query()->create([
                'project_id' => $project->id,
                'sale_id' => $sale->id,
                'from_client_id' => $from,
                'to_client_id' => $to->id,
                'fee' => $fee,
                'transferred_at' => $data['transferred_at'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);
            $sale->update(['client_id' => $to->id]);
            Contract::query()->where('sale_id', $sale->id)->update(['client_id' => $to->id]);
            if ($fee > 0.00001) {
                // رسوم التنازل إيراد للمشروع (قبض يدوي في الصندوق)
                TreasuryTransaction::query()->create([
                    'project_id' => $project->id,
                    'type' => 'revenue',
                    'amount' => $fee,
                    'reference_type' => null,
                    'reference_id' => null,
                    'description' => 'رسوم تنازل — بيعة SL-'.$sale->id,
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                ]);
            }
        });
        activity()->performedOn($sale)->causedBy($user)->withProperties(['from' => $from, 'to' => $to->id, 'fee' => $fee])->log('sale_transferred');

        return redirect()->route('sales.show', [$project, $sale])->with('success', 'اتنقلت الوحدة لـ «'.$to->name.'». المدفوع قبل كده والأقساط الباقية بقت على العميل الجديد.');
    }
}
