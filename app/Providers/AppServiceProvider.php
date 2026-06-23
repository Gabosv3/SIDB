<?php

namespace App\Providers;

use App\Filament\Resources\CompraResource;
use App\Filament\Resources\ProveedorResource;
use App\Filament\Resources\SucursalResource;
use App\Filament\Resources\UserResource;
use App\Listeners\RegistrarUltimoLogin;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\PagoCompra;
use App\Observers\CompraObserver;
use App\Observers\DetalleCompraObserver;
use App\Observers\PagoCompraObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FAQRCode\Google2FA;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Google2FA (usado por Filament MFA / AppAuthentication)
        $this->app->bind(Google2FA::class, fn () => new Google2FA());
    }

    public function boot(): void
    {
        // Registrar Observers
        Compra::observe(CompraObserver::class);
        DetalleCompra::observe(DetalleCompraObserver::class);
        PagoCompra::observe(PagoCompraObserver::class);

        // Recursos globales — no se filtran por sucursal (sin sucursal_id en su tabla)
        UserResource::scopeToTenant(false);
        SucursalResource::scopeToTenant(false);
        ProveedorResource::scopeToTenant(false);
        CompraResource::scopeToTenant(false);

        // Registrar último login (IP/dispositivo) en el perfil de empleado
        Event::listen(Login::class, RegistrarUltimoLogin::class);
    }
}
