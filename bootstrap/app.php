<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'pos.acceso'    => \App\Http\Middleware\EsVendedorOCobrador::class,
            'solo.vendedor' => \App\Http\Middleware\SoloVendedor::class,
            'solo.cobrador' => \App\Http\Middleware\SoloCobrador::class,
        ]);
        // Excluir del CSRF los endpoints AJAX de Mi WhatsApp (Baileys)
        $middleware->validateCsrfTokens(except: [
            'whatsapp-center/api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
