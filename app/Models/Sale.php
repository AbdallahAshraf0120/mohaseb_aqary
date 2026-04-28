<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'client_id',
        'property_id',
        'floor_number',
        'is_mezzanine',
        'apartment_model',
        'sale_price',
        'payment_type',
        'down_payment',
        'installment_months',
        'installment_start_date',
        'installment_plan',
        'sale_date',
        'broker_name',
        'notes',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'installment_start_date' => 'date',
        'installment_plan' => 'array',
        'is_mezzanine' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    /**
     * عرض عربي لنظام القسط حسب الخطة المحفوظة.
     */
    public function installmentScheduleTypeLabel(): string
    {
        $plan = $this->installment_plan ?? [];
        $type = $plan['schedule_type'] ?? 'monthly';

        return match ($type) {
            'quarterly' => 'كل 3 شهور',
            'semiannual' => 'كل 6 شهور',
            'monthly' => 'شهري',
            default => isset($plan['interval_months']) && (int) $plan['interval_months'] > 1
                ? 'كل '.(int) $plan['interval_months'].' شهر'
                : 'شهري',
        };
    }

    /**
     * جدول تواريخ الاستحقاق: أقساط منتظمة + دفعات ثانوية، مرتبة حسب التاريخ.
     *
     * @return list<array{number: int, due_date: Carbon, amount: float, kind: string, label: ?string}>
     */
    public function installmentScheduleRows(): array
    {
        if ($this->payment_type !== 'installment' || ! $this->installment_start_date) {
            return [];
        }
        $plan = $this->installment_plan ?? [];
        $count = max(0, (int) ($plan['installments_count'] ?? 0));
        $interval = max(1, (int) ($plan['interval_months'] ?? 1));

        $remainingTotal = (float) ($plan['remaining_amount'] ?? max(0, (float) $this->sale_price - (float) $this->down_payment));
        $baseForSchedule = (float) ($plan['installment_base_for_schedule'] ?? $remainingTotal);
        if ($baseForSchedule < 0) {
            $baseForSchedule = 0.0;
        }

        $mainRows = [];
        if ($count >= 1 && $baseForSchedule > 0) {
            $per = (float) ($plan['installment_amount'] ?? ($count > 0 ? round($baseForSchedule / $count, 2) : 0));
            $cursor = Carbon::parse($this->installment_start_date)->startOfDay();
            $acc = 0.0;
            for ($i = 1; $i <= $count; $i++) {
                $due = $cursor->copy();
                if ($i === $count) {
                    $amount = round($baseForSchedule - $acc, 2);
                } else {
                    $amount = round($per, 2);
                    $acc += $amount;
                }
                $mainRows[] = [
                    'number' => 0,
                    'due_date' => $due,
                    'amount' => $amount,
                    'kind' => 'installment',
                    'label' => null,
                ];
                if ($i < $count) {
                    $cursor->addMonths($interval);
                }
            }
        }

        $secondaryPlanned = $plan['secondary_payments'] ?? [];
        $secRows = [];
        if (is_array($secondaryPlanned)) {
            foreach ($secondaryPlanned as $sp) {
                if (! is_array($sp) || empty($sp['due_date']) || ! isset($sp['amount'])) {
                    continue;
                }
                $amt = (float) $sp['amount'];
                if ($amt <= 0) {
                    continue;
                }
                $secRows[] = [
                    'number' => 0,
                    'due_date' => Carbon::parse((string) $sp['due_date'])->startOfDay(),
                    'amount' => round($amt, 2),
                    'kind' => 'secondary',
                    'label' => trim((string) ($sp['label'] ?? '')) ?: 'دفعة ثانوية',
                ];
            }
        }

        $all = array_merge($mainRows, $secRows);
        if ($all === []) {
            return [];
        }

        usort($all, static function (array $a, array $b): int {
            $ta = $a['due_date']->timestamp;
            $tb = $b['due_date']->timestamp;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }

            return ($a['kind'] === 'secondary' ? 1 : 0) <=> ($b['kind'] === 'secondary' ? 1 : 0);
        });

        foreach ($all as $k => $row) {
            $all[$k]['number'] = $k + 1;
        }

        return $all;
    }

    /**
     * نفس جدول الاستحقاقات مع تقدير المسدد على كل بند (توزيع FIFO على مجموع تحصيلات العقد).
     *
     * @return list<array{number: int, due_date: Carbon, amount: float, paid: float, balance: float, status: string, kind: string, label: ?string}>
     */
    public function installmentScheduleWithPaymentSummary(): array
    {
        $schedule = $this->installmentScheduleRows();
        if ($schedule === []) {
            return [];
        }
        $revenues = $this->contract?->revenues ?? collect();
        $paidPool = (float) $revenues->sum(static fn ($r) => (float) $r->amount);
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
}
