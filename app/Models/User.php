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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;
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
        'last_login_at',
        'last_login_ip',
        'last_login_device',
        'account_status',
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
            'last_login_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Usuario {$eventName}");
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

    /** Perfil de empleado (datos personales/laborales, 1:1) */
    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class, 'user_id');
    }

    /** Documentos del empleado */
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'user_id');
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

    /** ¿La cuenta está bloqueada o desactivada administrativamente? */
    public function estaBloqueado(): bool
    {
        return $this->account_status !== 'activa';
    }

    /** ¿Tiene acceso a la API POS? */
    public function puedeUsarApi(): bool
    {
        return ! $this->estaBloqueado() && ($this->esVendedor() || $this->esCobrador());
    }

    /** Cierra todas las sesiones web y revoca todos los tokens del POS de este usuario. */
    public function cerrarTodasLasSesiones(): void
    {
        DB::table('sessions')->where('user_id', $this->id)->delete();
        $this->tokens()->delete();
        $this->forceFill(['remember_token' => Str::random(60)])->save();
    }

    // ── Filament: FilamentUser ───────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->estaBloqueado();
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
