<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** تنازل عن وحدة من عميل لعميل. */
class SaleTransfer extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'sale_id', 'from_client_id', 'to_client_id', 'fee', 'transferred_at', 'notes', 'created_by'];

    protected $casts = ['fee' => 'decimal:5', 'transferred_at' => 'date'];

    public function fromClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'from_client_id');
    }

    public function toClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'to_client_id');
    }
}
