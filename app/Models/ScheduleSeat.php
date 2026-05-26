<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduleSeat extends Model
{
    protected $fillable = ['schedule_id', 'row', 'column', 'label', 'is_available'];

    protected $casts = ['is_available' => 'boolean'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function passenger(): HasOne
    {
        return $this->hasOne(Passenger::class);
    }
}
