<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'description', 'discount_type', 'discount_value',
        'min_purchase', 'max_discount', 'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'valid_from'     => 'date',
        'valid_until'    => 'date',
        'is_active'      => 'boolean',
        'discount_value' => 'decimal:2',
        'min_purchase'   => 'decimal:2',
        'max_discount'   => 'decimal:2',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function calculateDiscount(float $price): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = $price * ($this->discount_value / 100);
            if ($this->max_discount) {
                $discount = min($discount, $this->max_discount);
            }
            return $discount;
        }

        return min($this->discount_value, $price);
    }

    public function validate(int $userId, float $totalPrice): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'Kode promo tidak aktif.'];
        }

        if (now()->lt($this->valid_from) || now()->gt($this->valid_until)) {
            return ['valid' => false, 'message' => 'Kode promo sudah kadaluarsa.'];
        }

        if ($totalPrice < $this->min_purchase) {
            return ['valid' => false, 'message' => 'Minimum pembelian Rp' . number_format((float) ($this->min_purchase ?? 0), 0, ',', '.')];
        }

        if ($this->usages()->where('user_id', $userId)->exists()) {
            return ['valid' => false, 'message' => 'Kode promo sudah pernah kamu gunakan.'];
        }

        return ['valid' => true, 'message' => 'Promo valid.'];
    }
}
