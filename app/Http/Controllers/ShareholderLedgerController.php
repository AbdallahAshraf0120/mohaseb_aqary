<?php

namespace App\Http\Controllers;

use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Services\ShareholderLedgerService;
use App\Support\CurrentProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShareholderLedgerController extends Controller
{
    public function __construct(
        private readonly ShareholderLedgerService $ledgerService,
    ) {}

    public function store(Request $request, Shareholder $shareholder): RedirectResponse
    {
        app(CurrentProject::class)->force((int) $shareholder->project_id);

        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(ShareholderLedgerEntry::TYPES))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'direction' => [
                Rule::requiredIf(fn () => $request->input('type') === ShareholderLedgerEntry::TYPE_ADJUSTMENT),
                'nullable',
                'string',
                Rule::in([
                    ShareholderLedgerEntry::DIRECTION_CREDIT,
                    ShareholderLedgerEntry::DIRECTION_DEBIT,
                ]),
            ],
        ]);

        $this->ledgerService->create($shareholder, $data, $request->user());

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', 'تم تسجيل حركة الجاري'.(
                ShareholderLedgerEntry::affectsCashbox((string) $data['type'])
                    ? ' وربطها بالصندوق.'
                    : '.'
            ));
    }

    public function destroy(Shareholder $shareholder, ShareholderLedgerEntry $ledger): RedirectResponse
    {
        abort_unless((int) $ledger->shareholder_id === (int) $shareholder->id, 404);
        abort_unless((int) $ledger->project_id === (int) $shareholder->project_id, 404);

        app(CurrentProject::class)->force((int) $shareholder->project_id);

        $this->ledgerService->delete($ledger);

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', 'تم حذف حركة الجاري وحركة الصندوق المرتبطة إن وُجدت.');
    }
}
