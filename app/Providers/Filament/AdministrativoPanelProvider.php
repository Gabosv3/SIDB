<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Models\ConfiguracionSistema;
use App\Models\Sucursal;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdministrativoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Carga la configuración desde BD de forma segura (la tabla puede no existir aún en migraciones)
        $config = null;
        try {
            if (Schema::hasTable('configuracion_sistema')) {
                $config = ConfiguracionSistema::instance();
            }
        } catch (\Throwable) {
        }

        $brandName    = $config?->app_name ?: config('app.name', 'SIDB');
        $logo         = $config?->logo         ? asset('storage/' . $config->logo)         : null;
        $logoDark     = $config?->logo_dark     ? asset('storage/' . $config->logo_dark)     : null;
        $favicon      = $config?->favicon       ? asset('storage/' . $config->favicon)       : null;
        $primaryColor = $config?->primary_color ?: null;

        $colors = $primaryColor
            ? ['primary' => Color::hex($primaryColor)]
            : ['primary' => Color::Amber];

        return $panel
            ->default()
            ->id('administrativo')
            ->path('administrativo')
            ->login()
            ->profile(page: EditProfile::class, isSimple: false)
            ->brandName($brandName)
            ->when($logo,     fn (Panel $p) => $p->brandLogo($logo)->brandLogoHeight('2.5rem'))
            ->when($logoDark, fn (Panel $p) => $p->darkModeBrandLogo($logoDark))
            ->when($favicon,  fn (Panel $p) => $p->favicon($favicon))
            ->colors($colors)
            ->renderHook(
                'panels::styles.after',
                fn (): string => view('filament.login-styles')->render(),
            )
            ->renderHook(
                'panels::styles.after',
                fn (): string => '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />',
            )
            ->renderHook(
                'panels::scripts.after',
                fn (): string => '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>',
            )
            // ── Botón Monitor POS en el sidebar ──────────────────────────────────
            ->renderHook(
                'panels::sidebar.nav.end',
                fn (): string => view('filament.pos-sidebar-button')->render(),
            )
            // ── Botón WhatsApp en el sidebar ─────────────────────────────────────
            ->renderHook(
                'panels::sidebar.nav.end',
                fn (): string => view('filament.whatsapp-sidebar-button')->render(),
            )
            // ── Autenticación de Dos Factores (2FA) ───────────────────────────
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->brandName(config('app.name', 'SIDB')),
            ])
            // ── Multitenencia: Sucursal ───────────────────────────────────────
            ->tenant(Sucursal::class, slugAttribute: 'id')
            ->tenantMenu(fn () => filled(\Filament\Facades\Filament::getTenant()))
            ->tenantMenuItems([])
            // ── Recursos y páginas ────────────────────────────────────────────
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('POS'),
                NavigationGroup::make('Cobros'),
                NavigationGroup::make('Ventas'),
                NavigationGroup::make('Comercial'),
                NavigationGroup::make('Comunicación'),
                NavigationGroup::make('Compras'),
                NavigationGroup::make('Inventario'),
                NavigationGroup::make('Administración'),
                NavigationGroup::make('Sistema'),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            // ── Plugins ───────────────────────────────────────────────────────
            ->plugins([
                FilamentShieldPlugin::make()
                    ->scopeToTenant(false),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
