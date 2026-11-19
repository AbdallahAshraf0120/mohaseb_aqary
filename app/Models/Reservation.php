<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * حجز وحدة بعربون لمدة محددة قبل التعاقد.
 */
class Reservation extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id', 'property_id', 'floor_number', 'is_mezzanine', 'unit_number', 'client_id',
        'deposit', 'receipt_no', 'reserved_at', 'expires_at', 'status', 'deposit_outcome', 'sale_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'is_mezzanine' => 'boolean',
        'deposit' => 'decimal:5',
        'reserved_at' => 'date',
        'expires_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reservation $r): void {
            if ($r->receipt_no === null && (float) $r->deposit > 0) {
                $r->receipt_no = \App\Support\DocumentNumber::next($r->project_id ? (int) $r->project_id : null);
            }
        });
    }

    /** الحجوزات اللي لسه قافلة الوحدة: سارية ومدتها ما خلصتش. */
    public function scopeBlocking($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString()));
    }

    public function isExpired(): bool
    {
        return $this->status === 'active' && $this->expires_at !== null && $this->expires_at->lt(now()->startOfDay());
    }

    public function statusLabel(): string
    {
        if ($this->isExpired()) {
            return 'انتهت مدته';
        }

        return match ($this->status) {
            'active' => 'ساري',
            'converted' => 'اتحوّل لبيعة',
            'cancelled' => $this->deposit_outcome === 'forfeited' ? 'اتلغى (العربون اتصادر)' : 'اتلغى (العربون اترد)',
            default => $this->status,
        };
    }

    public function unitLabel(): string
    {
        $p = $this->property;
        if ($p && $p->isStandaloneUnits()) {
            return $p->unitWord().' '.$this->unit_number;
        }
        if ((int) $this->floor_number === 0 && ! $this->is_mezzanine) {
            return 'أرضي · محل '.$this->unit_number;
        }

        return ($this->is_mezzanine ? 'ميزانين ' : 'دور ').$this->floor_number.' · شقة '.$this->unit_number;
    }

    public function receiptNumber(): string
    {
        return \App\Support\DocumentNumber::format($this->receipt_no ? (int) $this->receipt_no : null);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
