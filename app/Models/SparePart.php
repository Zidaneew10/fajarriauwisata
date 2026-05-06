<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    protected $fillable = [
        'code', 'name', 'unit', 'stock',
        'safety_stock', 'rop', 'lead_time',
        'avg_daily_usage', 'price', 'description',
    ];

    protected $casts = [
        'price'           => 'decimal:2',
        'avg_daily_usage' => 'decimal:2',
        'lead_time'       => 'decimal:2',
    ];

    public function sparePartIns(): HasMany
    {
        return $this->hasMany(SparePartIn::class);
    }

    public function sparePartOuts(): HasMany
    {
        return $this->hasMany(SparePartOut::class);
    }

    /**
     * Hitung ROP otomatis.
     * ROP = (avg_daily_usage × lead_time) + safety_stock
     */
    public function calculateRop(): int
    {
        return (int) ceil(($this->avg_daily_usage * $this->lead_time) + $this->safety_stock);
    }

    /**
     * Apakah stok sudah di bawah ROP?
     */
    public function needsReorder(): bool
    {
        return $this->stock <= $this->rop;
    }

    /**
     * Apakah stok sudah di bawah safety stock?
     */
    public function isCritical(): bool
    {
        return $this->stock <= $this->safety_stock;
    }

    /**
     * Hitung avg_daily_usage dari histori keluar.
     * Ambil 30 hari terakhir.
     */
    public function recalculateAvgUsage(): void
    {
        $totalOut = $this->sparePartOuts()
            ->where('used_at', '>=', now()->subDays(30))
            ->sum('quantity');

        $this->avg_daily_usage = $totalOut / 30;
        $this->rop             = $this->calculateRop();
        $this->save();
    }
}
