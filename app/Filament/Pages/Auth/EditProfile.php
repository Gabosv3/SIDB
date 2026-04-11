<?php

namespace App\Filament\Pages\Auth;

use App\Models\Sucursal;
use Carbon\Carbon;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class EditProfile extends BaseEditProfile
{
    protected Width | string | null $maxWidth = Width::TwoExtraLarge;

    public function mount(): void
    {
        parent::mount();

        if (! Filament::getTenant() && Filament::hasTenancy() && Filament::auth()->check()) {
            $tenant = Filament::getUserDefaultTenant(Filament::auth()->user());
            if ($tenant) {
                Filament::setTenant($tenant);
            }
        }
    }

    // ── Contraseña siempre visible (sin ->visible()) ─────────────────────────
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Nueva contraseña')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->dehydrated(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Confirmar nueva contraseña')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('new-password')
            ->required(fn ($get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    // ── Formulario principal ─────────────────────────────────────────────────
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información personal')
                    ->description('Actualiza tu nombre y dirección de correo electrónico.')
                    ->icon('heroicon-m-user-circle')
                    ->columns(1)
                    ->schema([
                        $this->getNameFormComponent()->columnSpan(1),
                        $this->getEmailFormComponent()->columnSpan(1),
                    ]),

                Section::make('Cambiar contraseña')
                    ->description('Deja los campos en blanco si no deseas cambiar tu contraseña.')
                    ->icon('heroicon-m-lock-closed')
                    ->columns(1)
                    ->schema([
                        $this->getPasswordFormComponent()->columnSpan(1),
                        $this->getPasswordConfirmationFormComponent()->columnSpan(1),
                        $this->getCurrentPasswordFormComponent()->columnSpan(2),
                    ]),
            ]);
    }

    // ── Contenido de la página (incluye 2FA + secciones extra) ───────────────
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                ...array_filter([
                    $this->getMultiFactorAuthenticationContentComponent(),
                    $this->getSucursalesSection(),
                    $this->getSesionesSection(),
                ]),
            ]);
    }

    // ── Sucursales asignadas (solo lectura) ───────────────────────────────────
    protected function getSucursalesSection(): Section
    {
        $user      = Filament::auth()->user();
        $sucursales = $user->sucursales ?? collect();

        $html = '<div style="display:grid;gap:0.75rem;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">';

        foreach ($sucursales as $s) {
            $activo     = $s->activo ?? true;
            $badgeColor = $activo ? 'color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0' : 'color:#b91c1c;background:#fef2f2;border:1px solid #fecaca';
            $badgeText  = $activo ? 'Activa' : 'Inactiva';
            $dotColor   = $activo ? '#22c55e' : '#ef4444';
            $codigo     = e($s->codigo ?? '—');
            $nombre     = e($s->nombre ?? '—');
            $direccion  = e($s->direccion ?? 'Sin dirección');

            $html .= <<<HTML
            <div style="display:flex;align-items:flex-start;gap:0.625rem;border-radius:0.75rem;border:1px solid #e5e7eb;background:#fff;padding:0.75rem;box-shadow:0 1px 2px 0 rgba(0,0,0,.05)">
                <div style="display:flex;width:2rem;height:2rem;flex-shrink:0;align-items:center;justify-content:center;border-radius:0.375rem;background:#fffbeb;color:#d97706">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                        <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 1 1-.832 1.248L12 3.901 3.416 9.624a.75.75 0 0 1-.832-1.248l9-6Z" />
                        <path fill-rule="evenodd" d="M20.25 10.332v9.918H21a.75.75 0 0 1 0 1.5H3a.75.75 0 0 1 0-1.5h.75v-9.918a.75.75 0 0 1 .634-.74A49.109 49.109 0 0 1 12 9c2.59 0 5.134.202 7.616.592a.75.75 0 0 1 .634.74Zm-7.5 2.418a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Zm3-.75a.75.75 0 0 1 .75.75v6.75a.75.75 0 0 1-1.5 0v-6.75a.75.75 0 0 1 .75-.75ZM9 12.75a.75.75 0 0 0-1.5 0v6.75a.75.75 0 0 0 1.5 0v-6.75Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div style="min-width:0;flex:1">
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                        <span style="font-size:0.875rem;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{$nombre}</span>
                        <span style="display:inline-flex;align-items:center;gap:0.25rem;border-radius:9999px;padding:0.125rem 0.5rem;font-size:0.75rem;font-weight:500;{$badgeColor}">
                            <span style="width:0.375rem;height:0.375rem;border-radius:9999px;background:{$dotColor}"></span>
                            {$badgeText}
                        </span>
                    </div>
                    <p style="margin-top:0.125rem;font-size:0.75rem;color:#6b7280">
                        <span style="font-family:monospace;color:#d97706">{$codigo}</span>
                        &nbsp;·&nbsp;{$direccion}
                    </p>
                </div>
            </div>
            HTML;
        }

        $html .= '</div>';

        return Section::make('Sucursales asignadas')
            ->description('Sucursales a las que tienes acceso en este sistema.')
            ->icon('heroicon-m-building-storefront')
            ->schema([Html::make($html)]);
    }

    // ── Sesiones activas ──────────────────────────────────────────────────────
    protected function getSesionesSection(): Section
    {
        $user             = Filament::auth()->user();
        $currentSessionId = session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get();

        $html = '<div style="display:grid;gap:0.75rem;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">';

        foreach ($sessions as $s) {
            $isCurrent = $s->id === $currentSessionId;
            $ua        = $s->user_agent ?? '';
            $ip        = e($s->ip_address ?? '—');
            $time      = Carbon::createFromTimestamp($s->last_activity)->diffForHumans();

            [$devicePath, $deviceLabel] = match (true) {
                str_contains($ua, 'Mobile') || str_contains($ua, 'Android') => [
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3" />',
                    'Móvil',
                ],
                str_contains($ua, 'Tablet') || str_contains($ua, 'iPad') => [
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-15a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25Z" />',
                    'Tablet',
                ],
                default => [
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3" />',
                    'Escritorio',
                ],
            };

            $browser = match (true) {
                str_contains($ua, 'Edg/')    => 'Edge',
                str_contains($ua, 'Chrome')  => 'Chrome',
                str_contains($ua, 'Firefox') => 'Firefox',
                str_contains($ua, 'Safari')  => 'Safari',
                str_contains($ua, 'Opera')   => 'Opera',
                default                       => 'Navegador desconocido',
            };

            $borderStyle = $isCurrent
                ? 'border:1px solid #fbbf24;background:#fffbeb'
                : 'border:1px solid #e5e7eb;background:#fff';

            $currentBadge = $isCurrent
                ? '<span style="display:inline-flex;align-items:center;border-radius:9999px;background:#fef3c7;color:#92400e;padding:0.125rem 0.5rem;font-size:0.75rem;font-weight:500;border:1px solid #fde68a">Sesión actual</span>'
                : '';

            $html .= <<<HTML
            <div style="display:flex;align-items:flex-start;gap:0.625rem;border-radius:0.75rem;{$borderStyle};padding:0.75rem;box-shadow:0 1px 2px 0 rgba(0,0,0,.05)">
                <div style="display:flex;width:2rem;height:2rem;flex-shrink:0;align-items:center;justify-content:center;border-radius:0.375rem;background:#f3f4f6;color:#6b7280">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                        {$devicePath}
                    </svg>
                </div>
                <div style="min-width:0;flex:1">
                    <div style="display:flex;align-items:center;gap:0.375rem;flex-wrap:wrap">
                        <span style="font-size:0.875rem;font-weight:600;color:#111827">{$deviceLabel} — {$browser}</span>
                        {$currentBadge}
                    </div>
                    <p style="margin-top:0.125rem;font-size:0.75rem;color:#6b7280">
                        <span style="font-family:monospace">{$ip}</span>
                        &nbsp;·&nbsp;{$time}
                    </p>
                </div>
            </div>
            HTML;
        }

        $html .= '</div>';

        return Section::make('Sesiones activas')
            ->description('Dispositivos con sesión iniciada en tu cuenta.')
            ->icon('heroicon-m-computer-desktop')
            ->schema([Html::make($html)]);
    }

    public function getExtraContentSchema(): array
    {
        return [];
    }
}
