<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Sucursales a las que pertenece este usuario (multitenencia) */
    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class);
    }

    /** Perfil de vendedor vinculado */
    public function vendedor(): HasOne
    {
        return $this->hasOne(Vendedor::class, 'user_id');
    }

    /** Perfil de cobrador vinculado */
    public function cobrador(): HasOne
    {
        return $this->hasOne(Cobrador::class, 'user_id');
    }

    /** ¿Este usuario puede operar como vendedor en el POS? */
    public function esVendedor(): bool
    {
        return $this->vendedor()->where('activo', true)->exists();
    }

    /** ¿Este usuario puede operar como cobrador? */
    public function esCobrador(): bool
    {
        if ($this->cobrador()->where('activo', true)->exists()) {
            return true;
        }
        // Vendedor con flag es_cobrador también cuenta
        return $this->vendedor()->where('activo', true)->where('es_cobrador', true)->exists();
    }

    /** ¿Tiene acceso a la API POS? */
    public function puedeUsarApi(): bool
    {
        return $this->esVendedor() || $this->esCobrador();
    }

    // ── Filament: FilamentUser ───────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Controlar acceso con roles/permisos si se requiere
    }

    // ── Filament: HasTenants ─────────────────────────────────────────────────

    public function getTenants(Panel $panel): Collection
    {
        return $this->sucursales;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->sucursales()->whereKey($tenant)->exists();
    }
}
