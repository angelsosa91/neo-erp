<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PosSession extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'session_token',
        'authentication_method',
        'rfid_code',
        'terminal_identifier',
        'opened_at',
        'last_activity_at',
        'closed_at',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con ventas
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Generar token único de sesión
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Actualizar timestamp de última actividad
     */
    public function updateActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Verificar si la sesión ha expirado por inactividad
     */
    public function isExpired(int $timeoutMinutes = 10): bool
    {
        if ($this->status !== 'active') {
            return true;
        }

        $timeoutAt = $this->last_activity_at->addMinutes($timeoutMinutes);

        return now()->greaterThan($timeoutAt);
    }

    /**
     * Cerrar sesión
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Marcar sesión como expirada
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'closed_at' => now(),
        ]);
    }

    /**
     * Scope: Sesiones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Sesiones para un usuario específico
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Sesiones abiertas hoy
     */
    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }

    /**
     * Obtener duración de la sesión en minutos
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->closed_at) {
            // Sesión aún activa
            return $this->opened_at->diffInMinutes(now());
        }

        return $this->opened_at->diffInMinutes($this->closed_at);
    }

    /**
     * Obtener duración formateada
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->getDurationInMinutes();

        if (!$minutes) {
            return '0 min';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}min";
        }

        return "{$mins}min";
    }

    /**
     * Obtener sesión activa para un usuario
     */
    public static function getActiveForUser(int $userId): ?self
    {
        return self::forUser($userId)
            ->active()
            ->orderBy('opened_at', 'desc')
            ->first();
    }

    /**
     * Crear nueva sesión POS
     */
    public static function createSession(
        User $user,
        string $authMethod,
        ?string $rfidCode = null,
        ?string $terminalId = null
    ): self {
        return self::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'session_token' => self::generateToken(),
            'authentication_method' => $authMethod,
            'rfid_code' => $rfidCode,
            'terminal_identifier' => $terminalId,
            'opened_at' => now(),
            'last_activity_at' => now(),
            'status' => 'active',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
