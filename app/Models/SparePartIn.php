<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePartIn extends Model
{
    protected $fillable = [
        'spare_part_id',
        'user_id',
        'reference_number',
        'quantity',
        'price_per_unit',
        'supplier',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'received_at'    => 'date',
        'price_per_unit' => 'decimal:2',
    ];

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(SparePartInItem::class);
    }
}
