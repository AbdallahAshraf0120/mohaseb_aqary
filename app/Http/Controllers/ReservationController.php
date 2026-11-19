<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\CashboxBalanceService;
use App\Services\ClientDirectory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * حجز وحدة بعربون لمدة محددة → يتحوّل لبيعة، أو يتلغي (العربون يترد أو يتصادر).
 * العربون بيدخل الصندوق كأمانة (مش إيراد) لحد ما الحجز يتحوّل أو يتصادر.
 */
class ReservationController extends Controller
{
    public function index(Project $project, Request $request): View
    {
        $status = (string) $request->query('status', 'active');
        $q = Reservation::query()->with(['client:id,name,phone', 'property:id,name,unit_kind', 'sale:id']);
        match ($status) {
            'active' => $q->blocking(),
            'expired' => $q->where('status', 'active')->whereDate('expires_at', '<', now()->toDateString()),
            'done' => $q->whereIn('status', ['converted', 'cancelled']),
            default => null,
        };

        return view('reservations.index', [
            'title' => 'الحجوزات | Mohaseb Aqary',
            'pageTitle' => 'حجوزات الوحدات',
            'project' => $project,
            'reservations' => $q->latest('id')->paginate(25)->withQueryString(),
            'status' => $status,
            'kpis' => [
                'active' => Reservation::query()->blocking()->count(),
                'deposits' => (float) Reservation::query()->where('status', 'active')->sum('deposit'),
                'expired' => Reservation::query()->where('status', 'active')->whereDate('expires_at', '<', now()->toDateString())->count(),
            ],
        ]);
    }

    public function create(Project $project): View
    {
        $props = Property::query()->select('id', 'name', 'floors_count', 'registered_floors', 'ground_floor_shops_count', 'has_mezzanine',
            'mezzanine_floors', 'mushaa_floors', 'apartment_models', 'apartments_per_floor', 'unit_kind')->orderBy('name')->get();

        return view('reservations.create', [
            'title' => 'حجز وحدة | Mohaseb Aqary',
            'pageTitle' => 'حجز وحدة بعربون',
            'project' => $project,
            'properties' => $props,
            'takenUnits' => app(SaleController::class)->takenUnits(null),
            'existingClients' => \App\Models\Client::query()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'national_id']),
        ]);
    }

    public function store(Project $project, Request $request, ClientDirectory $directory): RedirectResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'floor_number' => ['required', 'integer', 'min:0'],
            'is_mezzanine' => ['sometimes', 'boolean'],
            'unit_number' => ['required', 'integer', 'min:1', 'max:500'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:30'],
            'client_national_id' => ['nullable', 'string', 'max:50'],
            'deposit' => ['required', 'numeric', 'min:0', 'max:'.\App\Support\Money::MAX_AMOUNT],
            'reserved_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:reserved_at'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $property = Property::query()->findOrFail((int) $data['property_id']);
        $isMezz = (bool) ($data['is_mezzanine'] ?? false);
        $floor = (int) $data['floor_number'];
        $unit = (int) $data['unit_number'];
        $max = $property->unitsOnFloor($floor, $isMezz);
        if ($max > 0 && $unit > $max) {
            return back()->withInput()->with('error', 'رقم الوحدة أكبر من عدد الوحدات ('.$max.').');
        }
        $sold = Sale::query()->where('property_id', $property->id)->where('floor_number', $floor)->where('is_mezzanine', $isMezz)
            ->where('unit_number', $unit)->whereNotIn('approval_status', ['rejected', 'cancelled'])->exists();
        $reserved = Reservation::query()->blocking()->where('property_id', $property->id)->where('floor_number', $floor)
            ->where('is_mezzanine', $isMezz)->where('unit_number', $unit)->exists();
        if ($sold || $reserved) {
            return back()->withInput()->with('error', $sold ? 'الوحدة دي مبيعة.' : 'الوحدة دي محجوزة بالفعل.');
        }

        $user = $request->user();
        $reservation = DB::transaction(function () use ($data, $directory, $property, $floor, $isMezz, $unit, $user, $project) {
            $client = $directory->findOrCreate([
                'name' => $data['client_name'], 'phone' => $data['client_phone'], 'national_id' => $data['client_national_id'] ?? null,
            ]);
            $r = Reservation::query()->create([
                'property_id' => $property->id, 'floor_number' => $floor, 'is_mezzanine' => $isMezz, 'unit_number' => $unit,
                'client_id' => $client->id, 'deposit' => round((float) $data['deposit'], 5),
                'reserved_at' => $data['reserved_at'], 'expires_at' => $data['expires_at'],
                'status' => 'active', 'notes' => $data['notes'] ?? null, 'created_by' => $user?->id,
            ]);
            if ((float) $r->deposit > 0.00001) {
                TreasuryTransaction::query()->create([
                    'project_id' => $project->id, 'type' => 'revenue', 'amount' => $r->deposit,
                    'reference_type' => Reservation::class, 'reference_id' => $r->id,
                    'description' => 'عربون حجز (أمانة) — '.$client->name.' — '.$property->name,
                    'approval_status' => 'approved', 'approved_at' => now(), 'approved_by' => $user?->id,
                ]);
            }

            return $r;
        });

        return redirect()->route('reservations.index', $project)->with('success', 'اتسجل الحجز لحد '.$reservation->expires_at->format('Y-m-d').'. إيصال العربون: '.$reservation->receiptNumber());
    }

    public function cancel(Project $project, Reservation $reservation, Request $request): RedirectResponse
    {
        $data = $request->validate(['deposit_outcome' => ['required', 'in:refunded,forfeited']]);
        if ($reservation->status !== 'active') {
            return back()->with('error', 'الحجز ده مش ساري.');
        }
        $user = $request->user();
        if ($data['deposit_outcome'] === 'forfeited' && ! ($user instanceof User && $user->isAdmin())) {
            return back()->with('error', 'مصادرة العربون للأدمن بس.');
        }
        $deposit = (float) $reservation->deposit;
        if ($data['deposit_outcome'] === 'refunded' && $deposit > 0.00001) {
            try {
                app(CashboxBalanceService::class)->assertCanSpend((int) $project->id, $deposit);
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', 'رصيد الصندوق مش كفاية لرد العربون: '.$e->getMessage());
            }
        }

        DB::transaction(function () use ($reservation, $data, $deposit, $user, $project): void {
            $reservation->update(['status' => 'cancelled', 'deposit_outcome' => $data['deposit_outcome']]);
            if ($deposit <= 0.00001) {
                return;
            }
            if ($data['deposit_outcome'] === 'refunded') {
                TreasuryTransaction::query()->create([
                    'project_id' => $project->id, 'type' => 'expense', 'amount' => $deposit,
                    'reference_type' => Reservation::class, 'reference_id' => $reservation->id,
                    'description' => 'رد عربون حجز ملغي #'.$reservation->id,
                    'approval_status' => 'approved', 'approved_at' => now(), 'approved_by' => $user?->id,
                ]);
            } else {
                // العربون المصادر بقى إيراد للمشروع: نحوّل قيد الأمانة لقبض عادي
                TreasuryTransaction::query()->where('reference_type', Reservation::class)->where('reference_id', $reservation->id)
                    ->update(['reference_type' => null, 'reference_id' => null, 'description' => 'عربون حجز مصادر #'.$reservation->id]);
            }
        });

        return back()->with('success', $data['deposit_outcome'] === 'refunded' ? 'اتلغى الحجز واترد العربون.' : 'اتلغى الحجز والعربون اتصادر (اتسجل إيراد).');
    }
}
