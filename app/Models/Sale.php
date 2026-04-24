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
     * جدول تواريخ الاستحقاق ومبالغ الأقساط (متساوية مع تعديل آخر قسط للفرق التقريبي).
     *
     * @return list<array{number: int, due_date: Carbon, amount: float}>
     */
    public function installmentScheduleRows(): array
    {
        if ($this->payment_type !== 'installment' || ! $this->installment_start_date) {
            return [];
        }
        $plan = $this->installment_plan ?? [];
        $count = max(0, (int) ($plan['installments_count'] ?? 0));
        if ($count < 1) {
            return [];
        }
        $interval = max(1, (int) ($plan['interval_months'] ?? 1));
        $remaining = (float) ($plan['remaining_amount'] ?? max(0, (float) $this->sale_price - (float) $this->down_payment));
        $per = (float) ($plan['installment_amount'] ?? ($count > 0 ? round($remaining / $count, 2) : 0));

        $cursor = Carbon::parse($this->installment_start_date)->startOfDay();
        $rows = [];
        $acc = 0.0;
        for ($i = 1; $i <= $count; $i++) {
            $due = $cursor->copy();
            if ($i === $count) {
                $amount = round($remaining - $acc, 2);
            } else {
                $amount = round($per, 2);
                $acc += $amount;
            }
            $rows[] = [
                'number' => $i,
                'due_date' => $due,
                'amount' => $amount,
            ];
            if ($i < $count) {
                $cursor->addMonths($interval);
            }
        }

        return $rows;
    }

    /**
     * نفس جدول الأقساط مع تقدير المسدد على كل قسط (توزيع FIFO على مجموع تحصيلات العقد).
     *
     * @return list<array{number: int, due_date: Carbon, amount: float, paid: float, balance: float, status: string}>
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
            ];
        }

        return $out;
    }
}
