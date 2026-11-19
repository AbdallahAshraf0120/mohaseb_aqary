<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

/**
 * إيصالات استلام نقدية مطبوعة برقم مسلسل لكل مشروع.
 */
class ReceiptController extends Controller
{
    public function revenue(Project $project, Revenue $revenue): View
    {
        $revenue->load(['client', 'contract', 'sale.property:id,name']);
        abort_if($revenue->approval_status === 'rejected', 404);

        return view('receipts.show', $this->payload($project, [
            'number' => $revenue->receiptNumber(),
            'date' => $revenue->paid_at ?? $revenue->created_at,
            'client' => $revenue->client,
            'amount' => (float) $revenue->amount,
            'purpose' => ($revenue->category ?: 'تحصيل قسط'),
            'sale' => $revenue->sale,
            'method' => $revenue->methodLabel(),
            'cheque' => $revenue->payment_method === 'check' ? $revenue : null,
            'pending' => $revenue->approval_status === 'pending',
            'remaining' => $revenue->contract ? (float) $revenue->contract->remaining_amount : null,
            'notes' => $revenue->notes,
        ]));
    }

    public function saleDownPayment(Project $project, Sale $sale): View
    {
        abort_if((float) $sale->down_payment <= 0 || $sale->approval_status === 'rejected', 404);
        $sale->load(['client', 'contract', 'property:id,name']);

        return view('receipts.show', $this->payload($project, [
            'number' => $sale->downPaymentReceiptNumber(),
            'date' => $sale->sale_date,
            'client' => $sale->client,
            'amount' => (float) $sale->down_payment,
            'purpose' => 'مقدم تعاقد',
            'sale' => $sale,
            'method' => 'نقدي',
            'cheque' => null,
            'pending' => $sale->approval_status === 'pending',
            'remaining' => $sale->contract ? (float) $sale->contract->remaining_amount : null,
            'notes' => null,
        ]));
    }

    private function payload(Project $project, array $r): array
    {
        $setting = Setting::query()->first();

        return [
            'project' => $project,
            'r' => $r,
            'companyName' => $setting?->company_name ?: $project->name,
            'currency' => $setting?->currency ?: 'EGP',
        ];
    }
}
