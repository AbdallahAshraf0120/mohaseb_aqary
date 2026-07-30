<?php

namespace App\Http\Controllers;

use App\Models\LandParcelShareholder;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Services\ShareholderLedgerService;
use App\Support\CurrentProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ShareholderLedgerController extends Controller
{
    public function __construct(
        private readonly ShareholderLedgerService $ledgerService,
    ) {}

    public function store(Request $request, Shareholder $shareholder): RedirectResponse
    {
        $linkedProjectIds = ProjectShareholder::query()
            ->where('shareholder_id', (int) $shareholder->id)
            ->pluck('project_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $linkedLandIds = LandParcelShareholder::query()
            ->where('shareholder_id', (int) $shareholder->id)
            ->pluck('land_parcel_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $data = $request->validate([
            'destination' => ['required', 'string', 'regex:/^(project|land):\d+$/'],
            'type' => ['required', 'string', Rule::in(array_keys(ShareholderLedgerEntry::TYPES))],
            'amount' => ['required', 'numeric', 'min:0.00001'],
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

        [$kind, $id] = explode(':', $data['destination'], 2);
        $id = (int) $id;
        unset($data['destination']);

        if ($kind === 'project') {
            abort_unless(in_array($id, $linkedProjectIds, true), 422, 'المساهم غير مرتبط بهذا المشروع.');
            $data['project_id'] = $id;
            $data['land_parcel_id'] = null;
            app(CurrentProject::class)->force($id);
        } else {
            abort_unless(in_array($id, $linkedLandIds, true), 422, 'المساهم غير مرتبط بهذه الأرض.');
            $data['project_id'] = null;
            $data['land_parcel_id'] = $id;
            app(CurrentProject::class)->force(null);
        }

        try {
            $this->ledgerService->create($shareholder, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $cashboxNote = $kind === 'project' && ShareholderLedgerEntry::affectsCashbox((string) $data['type'])
            ? ' وربطها بصندوق المشروع.'
            : ($kind === 'land' ? ' (بدون صندوق مشروع — وجهة أرض).' : '.');

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', 'تم تسجيل حركة الجاري'.$cashboxNote);
    }

    public function allocate(Request $request, Shareholder $shareholder, ShareholderLedgerEntry $ledger): RedirectResponse
    {
        abort_unless((int) $ledger->shareholder_id === (int) $shareholder->id, 404);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'target_project_id' => ['required', 'integer', 'exists:projects,id'],
            'mode' => ['required', 'in:percentage,amount'],
            'percentage' => ['nullable', 'numeric', 'min:0.00001', 'max:100', 'required_if:mode,percentage'],
            'amount' => ['nullable', 'numeric', 'min:0.00001', 'required_if:mode,amount'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'target_project_id.required' => 'اختر المشروع الهدف.',
            'percentage.required_if' => 'أدخل النسبة المئوية.',
            'amount.required_if' => 'أدخل المبلغ.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_allocate_ledger_id', (int) $ledger->id);
        }

        $data = $validator->validated();

        $shareAmount = $data['mode'] === 'percentage'
            ? round(((float) $ledger->amount) * ((float) $data['percentage'] / 100), 5)
            : round((float) $data['amount'], 5);

        try {
            $result = $this->ledgerService->allocateToProject(
                $shareholder,
                $ledger,
                (int) $data['target_project_id'],
                $shareAmount,
                $request->user(),
                $data['notes'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage())
                ->with('open_allocate_ledger_id', (int) $ledger->id);
        }

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', 'تم توزيع '.number_format((float) $result['amount'], 5).' ج.م إلى المشروع المختار مع تحويلها لصندوقه.');
    }

    public function destroy(Shareholder $shareholder, ShareholderLedgerEntry $ledger): RedirectResponse
    {
        abort_unless((int) $ledger->shareholder_id === (int) $shareholder->id, 404);

        if ($ledger->project_id !== null) {
            app(CurrentProject::class)->force((int) $ledger->project_id);
        } else {
            app(CurrentProject::class)->force(null);
        }

        try {
            $this->ledgerService->delete($ledger);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('shareholders.show', $shareholder)
            ->with('success', 'تم حذف حركة الجاري وحركة الصندوق المرتبطة إن وُجدت.');
    }
}
