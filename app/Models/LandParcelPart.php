<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class LandParcelPart extends Model
{
    public const STATUSES = [
        'available' => 'متاح',
        'reserved' => 'محجوز',
        'sold' => 'مباع',
        'cancelled' => 'ملغى',
    ];

    protected $fillable = [
        'land_parcel_id',
        'name',
        'area_size',
        'status',
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
            'area_size' => 'decimal:5',
            'sale_price' => 'decimal:5',
            'sale_down_payment' => 'decimal:5',
            'sale_date' => 'date',
            'sale_installment_start_date' => 'date',
            'sale_installment_plan' => 'array',
        ];
    }

    public function landParcel(): BelongsTo
    {
        return $this->belongsTo(LandParcel::class, 'land_parcel_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LandParcelPayment::class, 'land_parcel_part_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    public function approvedPaidTotal(): float
    {
        return round((float) $this->payments()
            ->where('side', LandParcelPayment::SIDE_SALE)
            ->where('approval_status', 'approved')
            ->sum('amount'), 5);
    }

    public function remainingTotal(): float
    {
        return round(max(0, (float) $this->sale_price - $this->approvedPaidTotal()), 5);
    }

    /**
     * @return list<array{number: int, due_date: Carbon, amount: float, paid: float, balance: float, status: string, kind: string, label: ?string}>
     */
    public function installmentScheduleWithPaymentSummary(): array
    {
        $totalPrice = (float) $this->sale_price;
        $downPayment = (float) $this->sale_down_payment;
        $paymentType = $this->sale_payment_type ?? 'cash';
        $plan = $this->sale_installment_plan ?? [];
        $startDate = $this->sale_installment_start_date;
        $anchorDate = $this->sale_date;

        if ($totalPrice <= 0) {
            return [];
        }

        $rows = [];
        if ($downPayment > 0) {
            $rows[] = [
                'number' => 0,
                'due_date' => Carbon::parse(($anchorDate?->toDateString() ?: now()->toDateString()))->startOfDay(),
                'amount' => round($downPayment, 5),
                'kind' => 'down_payment',
                'label' => 'مقدم',
            ];
        }

        if ($paymentType === 'installment' && $startDate) {
            $count = max(0, (int) ($plan['installments_count'] ?? 0));
            $interval = max(1, (int) ($plan['interval_months'] ?? 1));
            $baseForSchedule = (float) ($plan['installment_base_for_schedule'] ?? max(0, $totalPrice - $downPayment));
            if ($count >= 1 && $baseForSchedule > 0) {
                $per = (float) ($plan['installment_amount'] ?? round($baseForSchedule / $count, 5));
                $cursor = Carbon::parse($startDate)->startOfDay();
                $acc = 0.0;
                for ($i = 1; $i <= $count; $i++) {
                    $due = $cursor->copy();
                    $amount = $i === $count
                        ? round($baseForSchedule - $acc, 5)
                        : round($per, 5);
                    if ($i < $count) {
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
        } elseif ($paymentType === 'cash' && $downPayment < $totalPrice) {
            $rows[] = [
                'number' => 0,
                'due_date' => Carbon::parse(($anchorDate?->toDateString() ?: now()->toDateString()))->startOfDay(),
                'amount' => round($totalPrice - $downPayment, 5),
                'kind' => 'other',
                'label' => 'المتبقي كاش',
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $a['due_date']->timestamp <=> $b['due_date']->timestamp);
        foreach ($rows as $k => $row) {
            $rows[$k]['number'] = $k + 1;
        }

        $paidPool = $this->approvedPaidTotal();
        $out = [];
        foreach ($rows as $row) {
            $due = (float) $row['amount'];
            $paid = round(min(max(0.0, $paidPool), $due), 5);
            $paidPool -= $paid;
            $balance = round($due - $paid, 5);
            $out[] = [
                'number' => $row['number'],
                'due_date' => $row['due_date'],
                'amount' => $due,
                'paid' => $paid,
                'balance' => $balance,
                'status' => $balance <= 0.00001 ? 'مسدد' : ($paid > 0 ? 'جزئي' : 'مستحق'),
                'kind' => $row['kind'],
                'label' => $row['label'],
            ];
        }

        return $out;
    }
}
