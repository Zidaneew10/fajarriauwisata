<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    protected $fillable = ['name', 'image', 'description'];

    public function busClasses(): BelongsToMany
    {
        return $this->belongsToMany(BusClass::class, 'bus_class_facilities');
    }
}
