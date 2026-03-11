<?php

namespace App\Providers;

use App\Filament\Resources\ClienteResource;
use App\Filament\Resources\MovimientoStockResource;
use App\Filament\Resources\ProductoResource;
use App\Filament\Resources\SucursalResource;
use App\Filament\Resources\UserResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Recursos globales — no se filtran por sucursal
        UserResource::scopeToTenant(false);
        SucursalResource::scopeToTenant(false);
        ClienteResource::scopeToTenant(false);
        ProductoResource::scopeToTenant(false);
        MovimientoStockResource::scopeToTenant(false);
    }
}
