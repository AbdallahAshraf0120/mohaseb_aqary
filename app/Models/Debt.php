<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'client_id',
        'creditor_name',
        'purchase_description',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * ذمّة على المشروع: مورد/جهة دائنة عند شراء ولم يُسدَّد الثمن بالكامل، أو سجل قديم مرتبط بعميل.
     */
    public function counterpartyLabel(): string
    {
        if (filled($this->creditor_name)) {
            return (string) $this->creditor_name;
        }

        return $this->client?->name ?? '—';
    }
}
