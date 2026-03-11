@props([
    'livewire' => null,
])

@php
    $renderHookScopes = $livewire?->getRenderHookScopes();
@endphp

<x-filament-panels::layout.base :livewire="$livewire">

    <div class="sidb-shell">

        {{-- ═ PANEL IZQUIERDO ══════════════════════════════ --}}
        <aside class="sidb-left" aria-hidden="true">
            <div class="sidb-left-noise"></div>

            <div class="sidb-left-body">

                {{-- Logo --}}
                <div class="sidb-logo-wrap">
                    <svg class="sidb-logo-icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3"  y="3"  width="19" height="25" rx="4" fill="white" opacity="0.9"/>
                        <rect x="26" y="3"  width="19" height="11" rx="4" fill="white" opacity="0.4"/>
                        <rect x="26" y="18" width="19" height="11" rx="4" fill="white" opacity="0.6"/>
                        <rect x="3"  y="32" width="42" height="13" rx="4" fill="white" opacity="0.25"/>
                    </svg>
                    <span class="sidb-logo-tag">SIDB</span>
                </div>

                {{-- Headline --}}
                <div class="sidb-headline">
                    <h1 class="sidb-h1">Sistema de<br>Inventario</h1>
                    <p class="sidb-sub">Distribuidora Birancesco Menijvar</p>
                </div>

                {{-- Separador --}}
                <div class="sidb-sep"></div>

                {{-- Features --}}
                <ul class="sidb-feat">
                    <li>
                        <svg class="sidb-feat-icon" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.25" opacity="0.4"/>
                            <path d="M5 8.5l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Control de inventario en tiempo real
                    </li>
                    <li>
                        <svg class="sidb-feat-icon" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.25" opacity="0.4"/>
                            <path d="M5 8.5l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Gestión de productos y categorías
                    </li>
                    <li>
                        <svg class="sidb-feat-icon" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.25" opacity="0.4"/>
                            <path d="M5 8.5l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Reportes y estadísticas avanzadas
                    </li>
                    <li>
                        <svg class="sidb-feat-icon" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.25" opacity="0.4"/>
                            <path d="M5 8.5l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Seguimiento de movimientos de stock
                    </li>
                </ul>

            </div>

            <p class="sidb-copy">&copy; {{ date('Y') }} Distribuidora Birancesco Menijvar</p>
        </aside>

        {{-- ═ PANEL DERECHO ═══════════════════════════════ --}}
        <div class="sidb-right">

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

            {{-- Topbar --}}
            <header class="sidb-topbar">
                <span class="sidb-topbar-label">Acceso al Sistema</span>
                @if (($hasTopbar ?? true) && filament()->auth()->check())
                    @if (filament()->hasDatabaseNotifications())
                        @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                            'lazy'     => filament()->hasLazyLoadedDatabaseNotifications(),
                            'position' => \Filament\Enums\DatabaseNotificationsPosition::Topbar,
                        ])
                    @endif
                    @if (filament()->hasUserMenu())
                        @livewire(Filament\Livewire\SimpleUserMenu::class)
                    @endif
                @endif
            </header>

            {{-- Formulario --}}
            <div class="sidb-form-wrap">
                <main class="sidb-form fi-simple-main">
                    {{ $slot }}
                </main>
            </div>

            {{-- Footer --}}
            <footer class="sidb-footer">
                Todos los derechos reservados &mdash; {{ date('Y') }}
            </footer>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}

        </div>

    </div>

</x-filament-panels::layout.base>