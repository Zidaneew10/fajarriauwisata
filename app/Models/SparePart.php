<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'stock',
        'price',
        'description',

        'maximum_daily_usage',
        'avg_daily_usage',
        'lead_time',

        'safety_stock',
        'rop',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'avg_daily_usage'     => 'decimal:2',
        'maximum_daily_usage' => 'decimal:2',
        'lead_time'           => 'decimal:2',
    ];

    /**
     * AUTO HITUNG SAAT CREATE / UPDATE
     */
   protected static function booted(): void
{
    static::saving(function (SparePart $sparePart) {

        // SS = (Pemakaian Maks - Pemakaian Rata-rata) × Lead Time
        $ss = (
            $sparePart->maximum_daily_usage
            - $sparePart->avg_daily_usage
        ) * $sparePart->lead_time;

        // Pastikan SS tidak negatif jika input tidak valid
        $sparePart->safety_stock = (int) ceil(max(0, $ss));

        // ROP = (Lead Time × Rata-rata) + SS
        $sparePart->rop = (int) ceil(
            ($sparePart->lead_time * $sparePart->avg_daily_usage)
            + $sparePart->safety_stock
        );
    });
}

    public function sparePartIns(): HasMany
    {
        return $this->hasMany(SparePartIn::class);
    }

    public function sparePartOuts(): HasMany
    {
        return $this->hasMany(SparePartOut::class);
    }

    /**
     * CEK PERLU REORDER
     */
    public function needsReorder(): bool
    {
        return $this->stock <= $this->rop;
    }

    /**
     * CEK STOK KRITIS
     */
    public function isCritical(): bool
    {
        return $this->stock <= $this->safety_stock;
    }
}
