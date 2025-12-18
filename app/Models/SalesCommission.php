<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesCommission extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'user_id',
        'item_type',
        'item_id',
        'item_name',
        'quantity',
        'sale_amount',
        'commission_percentage',
        'commission_amount',
        'status',
        'paid_at',
        'payment_reference',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sale_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Relación con venta
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relación con usuario (vendedor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación polimórfica con el item (Product o Service)
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calcular monto de comisión
     */
    public static function calculateCommission(float $amount, float $percentage): float
    {
        return round($amount * $percentage / 100, 2);
    }

    /**
     * Marcar comisión como pagada
     */
    public function markAsPaid(string $reference): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $reference,
        ]);
    }

    /**
     * Scope: Comisiones pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Comisiones pagadas
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Comisiones para un usuario específico
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Comisiones para un período
     */
    public function scopeForPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('sale_date', [$startDate, $endDate]);
        });
    }

    /**
     * Scope: Comisiones de este mes
     */
    public function scopeThisMonth($query)
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        return $query->forPeriod($start, $end);
    }

    /**
     * Crear comisión desde un item de venta (producto)
     */
    public static function createFromSaleItem(SaleItem $item, int $userId, float $commissionPercentage): self
    {
        $commissionAmount = self::calculateCommission($item->total, $commissionPercentage);

        return self::create([
            'tenant_id' => $item->sale->tenant_id,
            'sale_id' => $item->sale_id,
            'user_id' => $userId,
            'item_type' => 'product',
            'item_id' => $item->product_id,
            'item_name' => $item->product_name,
            'quantity' => $item->quantity,
            'sale_amount' => $item->total,
            'commission_percentage' => $commissionPercentage,
            'commission_amount' => $commissionAmount,
            'status' => 'pending',
        ]);
    }

    /**
     * Crear comisión desde un item de servicio
     */
    public static function createFromServiceItem(SaleServiceItem $item, int $userId, float $commissionPercentage): self
    {
        $commissionAmount = self::calculateCommission($item->total, $commissionPercentage);

        return self::create([
            'tenant_id' => $item->sale->tenant_id,
            'sale_id' => $item->sale_id,
            'user_id' => $userId,
            'item_type' => 'service',
            'item_id' => $item->service_id,
            'item_name' => $item->service_name,
            'quantity' => $item->quantity,
            'sale_amount' => $item->total,
            'commission_percentage' => $commissionPercentage,
            'commission_amount' => $commissionAmount,
            'status' => 'pending',
        ]);
    }

    /**
     * Obtener total de comisiones pendientes para un usuario
     */
    public static function getTotalPendingForUser(int $userId): float
    {
        return self::forUser($userId)
            ->pending()
            ->sum('commission_amount');
    }

    /**
     * Obtener total de comisiones pagadas para un usuario en un período
     */
    public static function getTotalPaidForUser(int $userId, string $startDate, string $endDate): float
    {
        return self::forUser($userId)
            ->paid()
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('commission_amount');
    }
}
