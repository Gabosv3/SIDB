<?php

namespace App\Providers;

use App\Filament\Resources\CompraResource;
use App\Filament\Resources\ProveedorResource;
use App\Filament\Resources\SucursalResource;
use App\Filament\Resources\UserResource;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\PagoCompra;
use App\Observers\CompraObserver;
use App\Observers\DetalleCompraObserver;
use App\Observers\PagoCompraObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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
    }
}
