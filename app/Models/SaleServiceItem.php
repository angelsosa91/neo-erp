<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleServiceItem extends Model
{
    protected $fillable = [
        'sale_id',
        'service_id',
        'service_name',
        'quantity',
        'unit_price',
        'tax_rate',
        'subtotal',
        'tax_amount',
        'total',
        'commission_percentage',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
    ];

    /**
     * Relación con venta
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relación con servicio
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Calcular valores del item (subtotal, IVA, total)
     * Usando la fórmula paraguaya donde el IVA está incluido en el precio
     */
    public function calculateValues(): void
    {
        // Calcular total base
        $this->total = $this->quantity * $this->unit_price;

        // Calcular IVA usando fórmula paraguaya: IVA = Monto × tasa / (100 + tasa)
        if ($this->tax_rate > 0) {
            $this->tax_amount = round($this->total * $this->tax_rate / (100 + $this->tax_rate), 2);
        } else {
            $this->tax_amount = 0;
        }

        // Calcular subtotal sin IVA
        $this->subtotal = $this->total - $this->tax_amount;
    }

    /**
     * Boot del modelo para calcular valores automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->calculateValues();
        });

        static::updating(function ($item) {
            if ($item->isDirty(['quantity', 'unit_price', 'tax_rate'])) {
                $item->calculateValues();
            }
        });
    }

    /**
     * Crear item desde un servicio
     */
    public static function createFromService(Sale $sale, Service $service, float $quantity = 1): self
    {
        return self::create([
            'sale_id' => $sale->id,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'quantity' => $quantity,
            'unit_price' => $service->price,
            'tax_rate' => $service->tax_rate,
            'commission_percentage' => $service->commission_percentage,
        ]);
    }
}
