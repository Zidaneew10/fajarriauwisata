<?php

namespace App\Filament\Traits;

use Illuminate\Support\Facades\Auth;

trait HasRoleAccess
{
    protected static function user()
    {
        return Auth::user();
    }

    public static function isAdmin(): bool
    {
        return self::user()?->hasRole('administrator') ?? false;
    }

    public static function isManager(): bool
    {
        return self::user()?->hasRole('manager') ?? false;
    }

    public static function isAdminOrManager(): bool
    {
        return self::user()?->hasAnyRole(['administrator', 'manager']) ?? false;
    }

    public static function isCustomerService(): bool
    {
        return self::user()?->hasRole('customer_service') ?? false;
    }

    public static function isPetugasLoket(): bool
    {
        return self::user()?->hasRole('petugas_loket') ?? false;
    }

    public static function isMontir(): bool
    {
        return self::user()?->hasRole('montir') ?? false;
    }

    public static function isDriver(): bool
    {
        return self::user()?->hasRole('driver') ?? false;
    }

    public static function canManageTickets(): bool
    {
        return self::user()?->hasAnyRole([
            'administrator',
            'manager',
            'customer_service',
            'petugas_loket',
        ]) ?? false;
    }

    public static function canManageInventory(): bool
    {
        return self::user()?->hasAnyRole([
            'administrator',
            'manager',
            'montir',
        ]) ?? false;
    }

    public static function canManageSparePartRequests(): bool
    {
        return self::user()?->hasAnyRole([
            'administrator',
            'manager',
            'montir',
            'driver',
        ]) ?? false;
    }

    public static function canManageMasterData(): bool
    {
        return self::user()?->hasAnyRole([
            'administrator',
            'manager',
        ]) ?? false;
    }
}
