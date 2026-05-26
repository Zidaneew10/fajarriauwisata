<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'discount_type', 'discount_value',
        'min_purchase', 'max_discount', 'usage_limit',
        'used_count', 'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'valid_from'   => 'date',
        'valid_until'  => 'date',
        'is_active'    => 'boolean',
        'discount_value' => 'decimal:2',
        'min_purchase'   => 'decimal:2',
        'max_discount'   => 'decimal:2',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /**
     * Hitung diskon berdasarkan total harga.
     */
    public function calculateDiscount(float $totalPrice): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = $totalPrice * ($this->discount_value / 100);
            if ($this->max_discount) {
                $discount = min($discount, $this->max_discount);
            }
            return $discount;
        }

        return min($this->discount_value, $totalPrice);
    }
}
