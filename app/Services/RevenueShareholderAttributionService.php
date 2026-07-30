<?php

namespace App\Services;

use App\Models\ProjectShareholder;
use App\Models\Revenue;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * يربط تحصيل المشروع بحساب مساهم (دخل حساب مين) ويُرحّل لجاري المساهم بدون تكرار الصندوق.
 */
class RevenueShareholderAttributionService
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
     * يزامن قيد الجاري مع حالة التحصيل: اعتماد → قيد دائن؛ غير معتمد/بدون مساهم → حذف القيد.
     */
    public function sync(Revenue $revenue, ?User $user = null): void
    {
        if (! Schema::hasColumn('revenues', 'received_by_shareholder_id')) {
            return;
        }

        $this->removeLedgerForRevenue((int) $revenue->id);

        $shareholderId = $revenue->received_by_shareholder_id !== null
            ? (int) $revenue->received_by_shareholder_id
            : null;

        if ($shareholderId === null || ($revenue->approval_status ?? '') !== 'approved') {
            return;
        }

        $projectId = (int) ($revenue->project_id ?? 0);
        if ($projectId < 1) {
            return;
        }

        $this->assertProjectShareholder($projectId, $shareholderId);

        $shareholder = Shareholder::query()->find($shareholderId);
        if (! $shareholder instanceof Shareholder) {
            return;
        }

        $amount = round((float) $revenue->amount, 5);
        if ($amount <= 0) {
            return;
        }

        $note = sprintf(
            'تحصيل مشروع #%d — %s — دخل حساب المساهم — إيصال #%d',
            $projectId,
            (string) ($revenue->category ?: 'تحصيل'),
            (int) $revenue->id
        );

        $payload = [
            'project_id' => $projectId,
            'type' => ShareholderLedgerEntry::TYPE_ADJUSTMENT,
            'direction' => ShareholderLedgerEntry::DIRECTION_CREDIT,
            'amount' => $amount,
            'entry_date' => $revenue->paid_at?->toDateString() ?? now()->toDateString(),
            'notes' => $note,
            'skip_cashbox' => true,
        ];

        if (Schema::hasColumn('shareholder_ledger_entries', 'revenue_id')) {
            $payload['revenue_id'] = (int) $revenue->id;
        }

        $this->shareholderLedgerService->create($shareholder, $payload, $user);
    }

    public function removeLedgerForRevenue(int $revenueId): void
    {
        if ($revenueId < 1 || ! Schema::hasColumn('shareholder_ledger_entries', 'revenue_id')) {
            return;
        }

        DB::transaction(function () use ($revenueId): void {
            $entries = ShareholderLedgerEntry::withoutProjectScope()
                ->where('revenue_id', $revenueId)
                ->get();

            foreach ($entries as $entry) {
                $this->shareholderLedgerService->delete($entry);
            }
        });
    }
}
