<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenue extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'amount',
        'category',
        'client_id',
        'contract_id',
        'sale_id',
        'source',
        'paid_at',
        'payment_method',
        'cheque_number',
        'cheque_bank',
        'cheque_due_date',
        'cheque_status',
        'cheque_status_at',
        'receipt_no',
        'received_by_shareholder_id',
        'notes',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:5',
        'paid_at' => 'date',
        'cheque_due_date' => 'date',
        'cheque_status_at' => 'datetime',
    ];

    public const CHEQUE_IN_HAND = 'in_hand';

    public const CHEQUE_DEPOSITED = 'deposited';

    public const CHEQUE_CLEARED = 'cleared';

    public const CHEQUE_BOUNCED = 'bounced';

    public const CHEQUE_LABELS = [
        self::CHEQUE_IN_HAND => 'في الخزنة',
        self::CHEQUE_DEPOSITED => 'اتودع البنك',
        self::CHEQUE_CLEARED => 'اتصرف',
        self::CHEQUE_BOUNCED => 'مرتد',
    ];

    public const METHOD_LABELS = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك',
        'wallet' => 'محفظة / إنستاباي',
    ];

    protected static function booted(): void
    {
        static::creating(function (Revenue $revenue): void {
            if ($revenue->receipt_no === null) {
                $revenue->receipt_no = \App\Support\DocumentNumber::next($revenue->project_id ? (int) $revenue->project_id : null);
            }
            if ($revenue->payment_method === 'check' && ! $revenue->cheque_status) {
                $revenue->cheque_status = self::CHEQUE_IN_HAND;
            }
            if ($revenue->payment_method !== 'check') {
                $revenue->cheque_status = null;
            }
        });
    }

    /**
     * التحصيل اللي بيتحسب فلوس فعلًا: معتمد، ولو شيك لازم يكون اتصرف.
     */
    public function scopeCounted($query)
    {
        $t = $query->getModel()->getTable();

        return $query->where($t.'.approval_status', 'approved')
            ->where(function ($w) use ($t): void {
                $w->where($t.'.payment_method', '!=', 'check')
                    ->orWhereNull($t.'.payment_method')
                    ->orWhereNull($t.'.cheque_status')
                    ->orWhere($t.'.cheque_status', self::CHEQUE_CLEARED);
            });
    }

    /** شيكات لسه ما اتصرفتش (في الخزنة أو في البنك). */
    public function scopeUnclearedCheques($query)
    {
        return $query->where('payment_method', 'check')
            ->whereIn('cheque_status', [self::CHEQUE_IN_HAND, self::CHEQUE_DEPOSITED]);
    }

    public function isCounted(): bool
    {
        if (($this->approval_status ?? 'approved') !== 'approved') {
            return false;
        }

        return $this->payment_method !== 'check'
            || $this->cheque_status === null
            || $this->cheque_status === self::CHEQUE_CLEARED;
    }

    public function isUnclearedCheque(): bool
    {
        return $this->payment_method === 'check'
            && in_array($this->cheque_status, [self::CHEQUE_IN_HAND, self::CHEQUE_DEPOSITED], true);
    }

    public function receiptNumber(): string
    {
        return \App\Support\DocumentNumber::format($this->receipt_no ? (int) $this->receipt_no : null);
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->payment_method] ?? (string) $this->payment_method;
    }

    public function chequeStatusLabel(): ?string
    {
        return $this->cheque_status ? (self::CHEQUE_LABELS[$this->cheque_status] ?? $this->cheque_status) : null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function receivedByShareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class, 'received_by_shareholder_id');
    }
}
