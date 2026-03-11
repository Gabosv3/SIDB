<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
