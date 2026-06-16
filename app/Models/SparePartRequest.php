<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePartRequest extends Model
{
    protected $fillable = [
        'user_id',
        'part_name',
        'quantity',
        'unit',
        'bus_info',
        'priority',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
