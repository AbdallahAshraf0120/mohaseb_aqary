<?php

namespace App\Http\Controllers;

use App\Models\FundTransfer;
use App\Models\LandParcel;
use App\Models\Project;
use App\Models\Shareholder;
use App\Services\FundTransferService;
use App\Support\LandTradingCashbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class FundTransferController extends Controller
{
    public function __construct(
        private readonly FundTransferService $fundTransferService,
    ) {}

    public function index(Request $request): View
    {
        if (! Schema::hasTable('fund_transfers')) {
            abort(503, 'قاعدة البيانات غير محدّثة. شغّل: php artisan migrate --force');
        }

        $landCashboxId = LandTradingCashbox::projectId();
        $projects = Project::query()
            ->listed()
            ->orderBy('name')
            ->get(['id', 'name']);

        $transfers = FundTransfer::query()
            ->with(['shareholder:id,name', 'sourceLandParcel:id,name', 'creator:id,name'])
            ->orderByDesc('transferred_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $parcels = LandParcel::query()->orderBy('name')->get(['id', 'name']);
        $shareholders = Shareholder::query()->orderBy('name')->get(['id', 'name']);

        return view('fund-transfers.index', [
            'title' => 'تحويلات الصناديق | Mohaseb Aqary',
            'pageTitle' => 'تحويلات الصناديق',
            'transfers' => $transfers,
            'projects' => $projects,
            'parcels' => $parcels,
            'shareholders' => $shareholders,
            'landCashboxId' => $landCashboxId,
            'landCashboxBalance' => $this->fundTransferService->balanceFor(FundTransfer::TYPE_LAND_CASHBOX, $landCashboxId),
            'projectBalances' => $projects->mapWithKeys(
                fn (Project $p) => [$p->id => $this->fundTransferService->balanceFor(FundTransfer::TYPE_PROJECT, (int) $p->id)]
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('fund_transfers')) {
            return back()->with('error', 'قاعدة البيانات غير محدّثة. شغّل: php artisan migrate --force');
        }

        $data = $request->validate([
            'from_type' => ['required', 'in:project,land_cashbox'],
            'from_id' => ['required', 'integer', 'min:1'],
            'to_type' => ['required', 'in:project,land_cashbox'],
            'to_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transferred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shareholder_id' => ['nullable', 'integer', 'exists:shareholders,id'],
            'source_land_parcel_id' => ['nullable', 'integer', 'exists:land_parcels,id'],
        ]);

        if ($data['from_type'] === FundTransfer::TYPE_LAND_CASHBOX) {
            $data['from_id'] = LandTradingCashbox::projectId();
        }
        if ($data['to_type'] === FundTransfer::TYPE_LAND_CASHBOX) {
            $data['to_id'] = LandTradingCashbox::projectId();
        }

        try {
            $this->fundTransferService->transfer($data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $redirect = $request->input('redirect_to') === 'land-cashbox'
            ? route('land-cashbox.index')
            : route('fund-transfers.index');

        return redirect($redirect)->with('success', 'تم تنفيذ التحويل بين الصناديق.');
    }
}
