<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusTrip extends Model
{
    use SoftDeletes;

    protected $fillable = ['trip_number'];

    public function busClasses(): HasMany
    {
        return $this->hasMany(BusClass::class);
    }

    public function routeSegments(): HasMany
    {
        return $this->hasMany(RouteSegment::class)->orderBy('sequence');
    }

    public function scheduleTemplates(): HasMany
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
