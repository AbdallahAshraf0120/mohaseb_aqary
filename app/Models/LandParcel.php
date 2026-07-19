<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class LandParcel extends Model
{
    public const STATUSES = [
        'owned' => 'مملوكة',
        'for_sale' => 'للبيع',
        'reserved' => 'محجوزة',
        'sold' => 'مباعة',
        'cancelled' => 'ملغاة',
    ];

    protected $fillable = [
        'created_by',
        'name',
        'location',
        'city',
        'area_size',
        'purchase_price_per_m2',
        'sale_price_per_m2',
        'deed_number',
        'status',
        'purchase_price',
        'planned_capital',
        'actual_capital',
        'purchase_date',
        'purchased_from',
        'purchase_phone',
        'purchase_payment_type',
        'purchase_down_payment',
        'purchase_installment_months',
        'purchase_installment_schedule',
        'purchase_installment_start_date',
        'purchase_installment_plan',
        'sale_price',
        'sale_date',
        'sold_to',
        'sale_phone',
        'sale_payment_type',
        'sale_down_payment',
        'sale_installment_months',
        'sale_installment_schedule',
        'sale_installment_start_date',
        'sale_installment_plan',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'area_size' => 'decimal:2',
            'purchase_price_per_m2' => 'decimal:2',
            'sale_price_per_m2' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'planned_capital' => 'decimal:2',
            'actual_capital' => 'decimal:2',
            'purchase_down_payment' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'sale_down_payment' => 'decimal:2',
            'purchase_date' => 'date',
            'sale_date' => 'date',
            'purchase_installment_start_date' => 'date',
            'sale_installment_start_date' => 'date',
            'purchase_installment_plan' => 'array',
            'sale_installment_plan' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parcelShareholders(): HasMany
    {
        return $this->hasMany(LandParcelShareholder::class, 'land_parcel_id');
    }

    public function shareholders(): BelongsToMany
    {
        return $this->belongsToMany(Shareholder::class, 'land_parcel_shareholder', 'land_parcel_id', 'shareholder_id')
            ->withPivot([
                'share_percentage',
                'total_investment',
                'planned_investment',
                'planned_percentage',
                'actual_investment',
                'actual_percentage',
            ])
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LandParcelPayment::class, 'land_parcel_id');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(LandParcelPart::class, 'land_parcel_id');
    }

    /** مساحة الأجزاء المحجوزة/المباعة/المتاحة (كل شيء غير الملغى). */
    public function allocatedPartsArea(?int $exceptPartId = null): float
    {
        $query = $this->parts()->whereNotIn('status', ['cancelled']);
        if ($exceptPartId !== null) {
            $query->where('id', '!=', $exceptPartId);
        }

        return round((float) $query->sum('area_size'), 2);
    }

    /** @deprecated استخدم allocatedPartsArea */
    public function soldPartsArea(): float
    {
        return $this->allocatedPartsArea();
    }

    public function remainingArea(?int $exceptPartId = null): ?float
    {
        if ($this->area_size === null) {
            return null;
        }

        return round(max(0, (float) $this->area_size - $this->allocatedPartsArea($exceptPartId)), 2);
    }

    /** نسبة الجزء من إجمالي مساحة الأرض. */
    public function areaPercentageOfTotal(float|int|string|null $partArea): ?float
    {
        $total = (float) ($this->area_size ?? 0);
        if ($total <= 0 || $partArea === null || $partArea === '') {
            return null;
        }

        return round(((float) $partArea / $total) * 100, 2);
    }

    public function totalFromPurchasePerM2(?float $area = null): ?float
    {
        $rate = (float) ($this->purchase_price_per_m2 ?? 0);
        $m2 = $area ?? (float) ($this->area_size ?? 0);
        if ($rate <= 0 || $m2 <= 0) {
            return null;
        }

        return round($rate * $m2, 2);
    }

    public function totalFromSalePerM2(?float $area = null): ?float
    {
        $rate = (float) ($this->sale_price_per_m2 ?? 0);
        $m2 = $area ?? (float) ($this->area_size ?? 0);
        if ($rate <= 0 || $m2 <= 0) {
            return null;
        }

        return round($rate * $m2, 2);
    }

    public function partsSaleTotal(): float
    {
        return round((float) $this->parts()->sum('sale_price'), 2);
    }

    public function partsCollectedTotal(): float
    {
        return round((float) $this->payments()
            ->where('side', LandParcelPayment::SIDE_SALE)
            ->where('approval_status', 'approved')
            ->whereNotNull('land_parcel_part_id')
            ->sum('amount'), 2);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function profit(): ?float
    {
        if ($this->sale_price === null) {
            return null;
        }

        return (float) $this->sale_price - (float) $this->purchase_price;
    }

    /** أساس النسبة المخططة: رأس المال المخطط / سعر شراء الأرض. */
    public function shareholderPercentageForInvestment(float|int|string $investment): float
    {
        $capital = round((float) ($this->planned_capital ?? $this->purchase_price ?? 0), 2);
        if ($capital <= 0) {
            return 0.0;
        }

        return round(((float) $investment / $capital) * 100, 2);
    }

    public function plannedCapitalAmount(): float
    {
        return round((float) ($this->planned_capital ?? $this->purchase_price ?? 0), 2);
    }

    public function actualCapitalAmount(): float
    {
        return round((float) ($this->actual_capital ?? 0), 2);
    }

    public function approvedPaidTotal(string $side, ?int $partId = null): float
    {
        $query = $this->payments()
            ->where('side', $side)
            ->where('approval_status', 'approved');

        if ($side === LandParcelPayment::SIDE_SALE) {
            if ($partId !== null) {
                $query->where('land_parcel_part_id', $partId);
            } else {
                $query->whereNull('land_parcel_part_id');
            }
        }

        return round((float) $query->sum('amount'), 2);
    }

    public function remainingTotal(string $side, ?int $partId = null): float
    {
        if ($side === LandParcelPayment::SIDE_SALE && $partId !== null) {
            $part = $this->parts()->whereKey($partId)->first();
            if (! $part instanceof LandParcelPart) {
                return 0.0;
            }

            return $part->remainingTotal();
        }

        $price = $side === LandParcelPayment::SIDE_PURCHASE
            ? (float) $this->purchase_price
            : (float) ($this->sale_price ?? 0);

        return round(max(0, $price - $this->approvedPaidTotal($side, $partId)), 2);
    }

    /** إجمالي محصّل البيع (بيع كامل + أجزاء). */
    public function allSaleCollectedTotal(): float
    {
        return round((float) $this->payments()
            ->where('side', LandParcelPayment::SIDE_SALE)
            ->where('approval_status', 'approved')
            ->sum('amount'), 2);
    }

    /**
     * @return list<array{number: int, due_date: Carbon, amount: float, kind: string, label: ?string}>
     */
    public function installmentScheduleRows(string $side): array
    {
        $isPurchase = $side === LandParcelPayment::SIDE_PURCHASE;
        $paymentType = $isPurchase ? ($this->purchase_payment_type ?? 'cash') : ($this->sale_payment_type ?? null);
        $totalPrice = $isPurchase ? (float) $this->purchase_price : (float) ($this->sale_price ?? 0);
        $downPayment = $isPurchase ? (float) $this->purchase_down_payment : (float) ($this->sale_down_payment ?? 0);
        $plan = $isPurchase ? ($this->purchase_installment_plan ?? []) : ($this->sale_installment_plan ?? []);
        $startDate = $isPurchase ? $this->purchase_installment_start_date : $this->sale_installment_start_date;
        $anchorDate = $isPurchase ? $this->purchase_date : $this->sale_date;

        if ($totalPrice <= 0) {
            return [];
        }

        $rows = [];
        if ($downPayment > 0) {
            $rows[] = [
                'number' => 0,
                'due_date' => Carbon::parse(($anchorDate?->toDateString() ?: now()->toDateString()))->startOfDay(),
                'amount' => round($downPayment, 2),
                'kind' => 'down_payment',
                'label' => 'مقدم',
            ];
        }

        if ($paymentType !== 'installment' || ! $startDate) {
            if ($paymentType === 'cash' && $downPayment < $totalPrice) {
                $rows[] = [
                    'number' => 0,
                    'due_date' => Carbon::parse(($anchorDate?->toDateString() ?: now()->toDateString()))->startOfDay(),
                    'amount' => round($totalPrice - $downPayment, 2),
                    'kind' => 'other',
                    'label' => 'المتبقي كاش',
                ];
            }

            return $this->numberScheduleRows($rows);
        }

        $count = max(0, (int) ($plan['installments_count'] ?? 0));
        $interval = max(1, (int) ($plan['interval_months'] ?? 1));
        $baseForSchedule = (float) ($plan['installment_base_for_schedule'] ?? max(0, $totalPrice - $downPayment));
        if ($baseForSchedule < 0) {
            $baseForSchedule = 0.0;
        }

        if ($count >= 1 && $baseForSchedule > 0) {
            $per = (float) ($plan['installment_amount'] ?? round($baseForSchedule / $count, 2));
            $cursor = Carbon::parse($startDate)->startOfDay();
            $acc = 0.0;
            for ($i = 1; $i <= $count; $i++) {
                $due = $cursor->copy();
                if ($i === $count) {
                    $amount = round($baseForSchedule - $acc, 2);
                } else {
                    $amount = round($per, 2);
                    $acc += $amount;
                }
                $rows[] = [
                    'number' => 0,
                    'due_date' => $due,
                    'amount' => $amount,
                    'kind' => 'installment',
                    'label' => 'قسط '.$i,
                ];
                if ($i < $count) {
                    $cursor->addMonths($interval);
                }
            }
        }

        return $this->numberScheduleRows($rows);
    }

    /**
     * @return list<array{number: int, due_date: Carbon, amount: float, paid: float, balance: float, status: string, kind: string, label: ?string}>
     */
    public function installmentScheduleWithPaymentSummary(string $side): array
    {
        $schedule = $this->installmentScheduleRows($side);
        if ($schedule === []) {
            return [];
        }

        $paidPool = $this->approvedPaidTotal($side, null);
        $out = [];
        foreach ($schedule as $row) {
            $due = (float) $row['amount'];
            $paid = round(min(max(0.0, $paidPool), $due), 2);
            $paidPool -= $paid;
            $balance = round($due - $paid, 2);
            $status = $balance <= 0.01 ? 'مسدد' : ($paid > 0 ? 'جزئي' : 'مستحق');
            $out[] = [
                'number' => $row['number'],
                'due_date' => $row['due_date'],
                'amount' => $due,
                'paid' => $paid,
                'balance' => $balance,
                'status' => $status,
                'kind' => $row['kind'] ?? 'installment',
                'label' => $row['label'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{number: int, due_date: Carbon, amount: float, kind: string, label: ?string}>  $rows
     * @return list<array{number: int, due_date: Carbon, amount: float, kind: string, label: ?string}>
     */
    private function numberScheduleRows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $ta = $a['due_date']->timestamp;
            $tb = $b['due_date']->timestamp;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }

            $rank = static fn (string $kind): int => match ($kind) {
                'down_payment' => 0,
                'installment' => 1,
                'secondary' => 2,
                default => 3,
            };

            return $rank((string) $a['kind']) <=> $rank((string) $b['kind']);
        });

        foreach ($rows as $k => $row) {
            $rows[$k]['number'] = $k + 1;
        }

        return $rows;
    }
}
