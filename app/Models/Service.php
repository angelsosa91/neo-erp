<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'code',
        'name',
        'description',
        'duration_minutes',
        'price',
        'tax_rate',
        'commission_percentage',
        'color',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'integer',
        'commission_percentage' => 'decimal:2',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con categoría
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación con items de servicio en ventas
     */
    public function saleServiceItems(): HasMany
    {
        return $this->hasMany(SaleServiceItem::class);
    }

    /**
     * Generar código único para el servicio
     */
    public static function generateCode(int $tenantId): string
    {
        $lastService = self::where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastService) {
            return 'SRV-00001';
        }

        // Extraer el número del último código
        $lastNumber = (int) substr($lastService->code, 4);
        $newNumber = $lastNumber + 1;

        return 'SRV-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular el monto de IVA usando la fórmula paraguaya
     * (El IVA está incluido en el precio)
     */
    public function calculateTax(float $amount): float
    {
        if ($this->tax_rate == 0) {
            return 0;
        }

        // Fórmula: IVA = Monto × tasa / (100 + tasa)
        return round($amount * $this->tax_rate / (100 + $this->tax_rate), 2);
    }

    /**
     * Calcular subtotal sin IVA
     */
    public function calculateSubtotal(float $amount): float
    {
        return $amount - $this->calculateTax($amount);
    }

    /**
     * Scope: Servicios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Servicios populares (más vendidos)
     */
    public function scopePopular($query, int $limit = 12)
    {
        return $query->active()
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->limit($limit);
    }

    /**
     * Scope: Búsqueda por nombre o código
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Obtener nombre con precio formateado
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} - $" . number_format($this->price, 0, ',', '.');
    }

    /**
     * Obtener duración formateada
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}min";
        }
    }
}
