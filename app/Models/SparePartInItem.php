<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePartInItem extends Model
{
    protected $fillable = [
        'spare_part_in_id', 'spare_part_id', 'quantity', 'price_per_unit',
    ];

    protected $casts = ['price_per_unit' => 'decimal:2'];

    protected static function booted(): void
    {
        static::created(function (SparePartInItem $item) {
            $item->sparePart->increment('stock', $item->quantity);
            $item->sparePart->recalculateAvgUsage();
        });

        static::deleted(function (SparePartInItem $item) {
            $item->sparePart->decrement('stock', $item->quantity);
        });
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function sparePartIn(): BelongsTo
    {
        return $this->belongsTo(SparePartIn::class);
    }
}
