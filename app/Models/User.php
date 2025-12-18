<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_active',
        'pos_pin',
        'rfid_code',
        'pos_enabled',
        'pos_require_rfid',
        'commission_percentage',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pos_pin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'pos_enabled' => 'boolean',
            'pos_require_rfid' => 'boolean',
            'commission_percentage' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    // ========================================
    // POS Methods
    // ========================================

    /**
     * Relación con sesiones POS
     */
    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    /**
     * Relación con la sesión POS activa
     */
    public function activePosSession(): HasOne
    {
        return $this->hasOne(PosSession::class)->where('status', 'active');
    }

    /**
     * Relación con comisiones
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(SalesCommission::class);
    }

    /**
     * Verificar PIN del POS
     */
    public function verifyPosPin(string $pin): bool
    {
        if (!$this->pos_pin) {
            return false;
        }

        return Hash::check($pin, $this->pos_pin);
    }

    /**
     * Establecer PIN del POS
     */
    public function setPosPin(string $pin): void
    {
        $this->pos_pin = Hash::make($pin);
        $this->save();
    }

    /**
     * Verificar si el usuario puede usar el POS
     */
    public function canUsePOS(): bool
    {
        return $this->pos_enabled && $this->is_active && !empty($this->pos_pin);
    }

    /**
     * Verificar si el POS requiere 2FA (PIN + RFID)
     */
    public function posRequires2FA(): bool
    {
        return $this->pos_require_rfid && !empty($this->rfid_code);
    }

    /**
     * Verificar código RFID
     */
    public function verifyRfidCode(string $code): bool
    {
        return $this->rfid_code === $code;
    }

    /**
     * Obtener sesión POS activa
     */
    public function getActivePosSession(): ?PosSession
    {
        return $this->activePosSession;
    }

    /**
     * Verificar si tiene una sesión POS activa
     */
    public function hasActivePosSession(): bool
    {
        return $this->activePosSession()->exists();
    }

    /**
     * Obtener total de comisiones pendientes
     */
    public function getPendingCommissions(): float
    {
        return SalesCommission::getTotalPendingForUser($this->id);
    }

    /**
     * Obtener porcentaje de comisión efectivo
     * (puede ser del usuario o heredado del item)
     */
    public function getEffectiveCommissionPercentage(): float
    {
        return $this->commission_percentage ?? 0;
    }
}
