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
use Illuminate\Support\Facades\Log;
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

        $this->registrarCapturaDeErroresFatales();
    }

    /**
     * Un error fatal real de PHP (memoria agotada, timeout del proceso, etc.)
     * puede saltarse por completo el manejador de excepciones de Laravel y no
     * dejar ningún rastro en storage/logs/laravel.log — solo se ve como un
     * 500 en blanco en el navegador. register_shutdown_function() sigue
     * ejecutándose incluso después de un fatal, así que es la única forma
     * confiable de enterarse de esos casos.
     */
    private function registrarCapturaDeErroresFatales(): void
    {
        register_shutdown_function(function () {
            $error = error_get_last();

            if (! $error || ! in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }

            try {
                Log::critical('Error fatal de PHP no capturado por el manejador de excepciones', [
                    'mensaje' => $error['message'],
                    'archivo' => $error['file'],
                    'linea' => $error['line'],
                    'url' => request()?->fullUrl(),
                    'metodo' => request()?->method(),
                    'usuario_id' => auth()->id(),
                ]);
            } catch (\Throwable $e) {
                // Si ni siquiera queda memoria/recursos para loguear, no hay nada más que hacer.
            }
        });
    }
}
