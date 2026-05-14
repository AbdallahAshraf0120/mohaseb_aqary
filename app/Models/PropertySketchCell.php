<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertySketchCell extends Model
{
    protected $fillable = [
        'property_id',
        'cell_key',
        'status',
        'note',
        'updated_by',
    ];

    /** الحالات اليدوية المسموح بها لخلية المخطط. */
    public const STATUSES = [
        'available' => 'متاح',
        'sold' => 'مباع',
        'pending' => 'تحت الاعتماد',
        'reserved' => 'محجوز (شفهي بدون عربون)',
        'viewing' => 'تحت العرض',
        'blocked' => 'غير متاح',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
