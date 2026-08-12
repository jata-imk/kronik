<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasTeams {
        switchTeam as private jetstreamSwitchTeam;
    }
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sucursal_principal_id',
        'current_sucursal_id',
        'status',
        'invited_at',
        'activated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
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
            'status' => UserStatus::class,
            'is_super_admin' => 'boolean',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class)->withTimestamps();
    }

    public function sucursalPrincipal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_principal_id');
    }

    public function currentSucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'current_sucursal_id');
    }

    public function belongsToSucursal(Sucursal $sucursal): bool
    {
        return $this->is_super_admin || $this->sucursales()->whereKey($sucursal->getKey())->exists();
    }

    public function switchSucursal(Sucursal $sucursal): bool
    {
        if (! $sucursal->activa || ! $this->belongsToSucursal($sucursal)) {
            return false;
        }

        return $this->forceFill(['current_sucursal_id' => $sucursal->id])->save();
    }

    public function switchTeam($team)
    {
        if (! $team?->activo) {
            return false;
        }

        return $this->jetstreamSwitchTeam($team);
    }

    public function allTeams()
    {
        return $this->ownedTeams->merge($this->teams)->where('activo', true)->sortBy('name');
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
