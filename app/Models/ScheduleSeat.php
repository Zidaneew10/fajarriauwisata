<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSeat extends Model
{
    protected $fillable = ['schedule_bus_id', 'row', 'column', 'is_available'];

    protected $casts = ['is_available' => 'boolean'];

    public function scheduleBus(): BelongsTo
    {
        return $this->belongsTo(ScheduleBus::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->column . $this->row;
    }
}
