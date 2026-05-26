<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['password' => 'hashed'];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            'administrator',
            'customer_service',
            'montir',
            'petugas_loket',
            'driver',
            'manager',
        ]);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
