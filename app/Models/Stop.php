<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stop extends Model
{
    protected $fillable = ['name', 'city', 'address', 'type'];

    public function routeSegments(): HasMany
    {
        return $this->hasMany(RouteSegment::class);
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type === 'terminal' ? '🏢' : '📍';
    }

    public function getLabelAttribute(): string
    {
        $label = "{$this->type_icon} {$this->city} — {$this->name}";
        if ($this->address) $label .= " ({$this->address})";
        return $label;
    }
}
