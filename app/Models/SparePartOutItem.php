<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class SparePartOutItem extends Model
{
    protected $fillable = [
        'spare_part_out_id', 'spare_part_id', 'quantity',
    ];

    protected static function booted(): void
    {
        static::creating(function (SparePartOutItem $item) {
            if ($item->sparePart->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$item->sparePart->name} tidak cukup. Stok saat ini: {$item->sparePart->stock} {$item->sparePart->unit}",
                ]);
            }
        });

        static::created(function (SparePartOutItem $item) {
            $item->sparePart->decrement('stock', $item->quantity);
            $item->sparePart->recalculateAvgUsage();
        });

        static::deleted(function (SparePartOutItem $item) {
            $item->sparePart->increment('stock', $item->quantity);
        });
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function sparePartOut(): BelongsTo
    {
        return $this->belongsTo(SparePartOut::class);
    }
}
