<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'sale_id',
        'client_id',
        'property_id',
        'start_date',
        'end_date',
        'total_price',
        'paid_amount',
        'remaining_amount',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    /**
     * مبلغ التحصيل المقترح للقسط التالي: أول بند في جدول البيعة ما زال له متبقي (نفس منطق FIFO في صفحة البيعة).
     * إن لم يوجد جدول أقساط، يُقترح المتبقي في العقد.
     *
     * @param  int|null  $excludeRevenueId  عند تعديل تحصيل: استبعاده من مجموع التحصيلات لحساب القسط القادم بدقة.
     */
    public function suggestedNextCollectionAmount(?int $excludeRevenueId = null): ?float
    {
        $remaining = round((float) $this->remaining_amount, 2);
        if ($remaining < 0.01) {
            return null;
        }

        $sale = $this->sale;
        if ($sale && $sale->payment_type === 'installment') {
            $this->loadMissing([
                'revenues' => static fn ($q) => $q->orderBy('paid_at')->orderBy('id'),
            ]);
            $originalRevenues = $this->revenues;
            if ($excludeRevenueId) {
                $filtered = $originalRevenues->filter(static fn (Revenue $r): bool => (int) $r->id !== $excludeRevenueId)->values();
                $this->setRelation('revenues', $filtered);
            }
            $sale->setRelation('contract', $this);

            $rows = $sale->installmentScheduleWithPaymentSummary();
            if ($excludeRevenueId) {
                $this->setRelation('revenues', $originalRevenues);
            }

            foreach ($rows as $row) {
                $balance = (float) ($row['balance'] ?? 0);
                if ($balance > 0.01) {
                    return round(min($balance, $remaining), 2);
                }
            }
        }

        return $remaining;
    }
}
