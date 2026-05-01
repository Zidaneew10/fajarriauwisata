<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Terminal extends Model
{
    protected $fillable = ['code', 'name', 'city', 'country'];

    public function routeSegments(): HasMany
    {
        return $this->hasMany(RouteSegment::class);
    }
}
