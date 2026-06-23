<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class RegistrarUltimoLogin
{
    public function handle(Login $event): void
    {
        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'last_login_device' => request()->userAgent(),
        ])->saveQuietly();
    }
}
