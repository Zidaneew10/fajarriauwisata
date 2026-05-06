<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'vendor.filament-panels.pages.auth.login';

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }
}
