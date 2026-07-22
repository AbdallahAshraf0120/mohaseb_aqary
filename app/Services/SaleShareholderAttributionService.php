<?php

namespace App\Services;

use App\Models\ProjectShareholder;
use App\Models\Sale;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * يربط مقدم البيعة بحساب مساهم (دخل حساب مين) ويُرحّل لجاري المساهم بدون تكرار الصندوق.
 */
class SaleShareholderAttributionService
{
    public function __construct(
        private readonly ShareholderLedgerService $shareholderLedgerService,
    ) {}

    public function assertProjectShareholder(int $projectId, int $shareholderId): void
    {
        $member = ProjectShareholder::query()
            ->where('project_id', $projectId)
            ->where('shareholder_id', $shareholderId)
            ->exists();

        if (! $member) {
            throw new InvalidArgumentException('المساهم (دخل حسابه) غير مرتبط بهذا المشروع.');
        }
    }

    /**
     * يزامن قيد الجاري مع حالة البيعة/المقدم: اعتماد ومقدم > 0 → قيد دائن؛ غير ذلك → حذف القيد.
     */
    public function sync(Sale $sale, ?User $user = null): void
    {
        if (! Schema::hasColumn('sales', 'received_by_shareholder_id')) {
            return;
        }

        $this->removeLedgerForSale((int) $sale->id);

        $shareholderId = $sale->received_by_shareholder_id !== null
            ? (int) $sale->received_by_shareholder_id
            : null;

        $amount = round((float) ($sale->down_payment ?? 0), 2);

        if ($shareholderId === null
            || $amount < 0.01
            || ($sale->approval_status ?? '') !== 'approved') {
            return;
        }

        $projectId = (int) ($sale->project_id ?? 0);
        if ($projectId < 1) {
            return;
        }

        $this->assertProjectShareholder($projectId, $shareholderId);

        $shareholder = Shareholder::query()->find($shareholderId);
        if (! $shareholder instanceof Shareholder) {
            return;
        }

        $note = sprintf('مقدم بيعة دخل حساب المساهم — بيعة #%d', (int) $sale->id);

        $payload = [
            'project_id' => $projectId,
            'type' => ShareholderLedgerEntry::TYPE_ADJUSTMENT,
            'direction' => ShareholderLedgerEntry::DIRECTION_CREDIT,
            'amount' => $amount,
            'entry_date' => $sale->sale_date?->toDateString() ?? now()->toDateString(),
            'notes' => $note,
            'skip_cashbox' => true,
        ];

        if (Schema::hasColumn('shareholder_ledger_entries', 'sale_id')) {
            $payload['sale_id'] = (int) $sale->id;
        }

        $this->shareholderLedgerService->create($shareholder, $payload, $user);
    }

    public function removeLedgerForSale(int $saleId): void
    {
        if ($saleId < 1 || ! Schema::hasColumn('shareholder_ledger_entries', 'sale_id')) {
            return;
        }

        DB::transaction(function () use ($saleId): void {
            $entries = ShareholderLedgerEntry::withoutProjectScope()
                ->where('sale_id', $saleId)
                ->get();

            foreach ($entries as $entry) {
                $this->shareholderLedgerService->delete($entry);
            }
        });
    }
}
