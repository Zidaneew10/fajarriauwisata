<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePartOut extends Model
{
    protected $fillable = [
        'user_id', 'bus_id',
        'reference_number', 'used_at',
        'reason', 'notes',
    ];

    protected $casts = ['used_at' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SparePartOutItem::class);
    }
}
