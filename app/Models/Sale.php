<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'user_id',
        'pos_session_id',
        'sale_number',
        'sale_date',
        'subtotal_exento',
        'subtotal_5',
        'iva_5',
        'subtotal_10',
        'iva_10',
        'total',
        'status',
        'journal_entry_id',
        'payment_method',
        'payment_type',
        'credit_days',
        'credit_due_date',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'credit_due_date' => 'date',
        'subtotal_exento' => 'decimal:2',
        'subtotal_5' => 'decimal:2',
        'iva_5' => 'decimal:2',
        'subtotal_10' => 'decimal:2',
        'iva_10' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación con el usuario que creó la venta
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la sesión POS (si fue creada desde POS)
     */
    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    /**
     * Relación con los items de la venta
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relación con los servicios vendidos
     */
    public function serviceItems(): HasMany
    {
        return $this->hasMany(SaleServiceItem::class);
    }

    /**
     * Relación con la cuenta por cobrar
     */
    public function accountReceivable()
    {
        return $this->hasOne(AccountReceivable::class);
    }

    /**
     * Relación con el asiento contable
     */
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Relación con las notas de crédito
     */
    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * Relación con la remisión origen (si fue creada desde remisión)
     */
    public function remission()
    {
        return $this->hasOne(Remission::class);
    }

    /**
     * Generar número de venta automático
     */
    public static function generateSaleNumber(int $tenantId): string
    {
        $lastSale = self::where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = intval(substr($lastSale->sale_number, -7));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'V-' . str_pad($newNumber, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular totales basado en los items (productos y servicios)
     */
    public function calculateTotals(): void
    {
        $subtotalExento = 0;
        $subtotal5 = 0;
        $iva5 = 0;
        $subtotal10 = 0;
        $iva10 = 0;

        // Combinar items de productos y servicios
        $allItems = $this->items->merge($this->serviceItems);

        foreach ($allItems as $item) {
            switch ($item->tax_rate) {
                case 0:
                    $subtotalExento += $item->subtotal;
                    break;
                case 5:
                    $subtotal5 += $item->subtotal;
                    $iva5 += $item->tax_amount;
                    break;
                case 10:
                    $subtotal10 += $item->subtotal;
                    $iva10 += $item->tax_amount;
                    break;
            }
        }

        $this->subtotal_exento = $subtotalExento;
        $this->subtotal_5 = $subtotal5;
        $this->iva_5 = $iva5;
        $this->subtotal_10 = $subtotal10;
        $this->iva_10 = $iva10;
        $this->total = $subtotalExento + $subtotal5 + $subtotal10;
    }

    /**
     * Confirmar una pre-venta (cambiar de draft a confirmed y descontar stock)
     */
    public function confirm(): bool
    {
        if ($this->status !== 'draft') {
            throw new \Exception('Solo se pueden confirmar ventas en estado borrador');
        }

        \DB::beginTransaction();
        try {
            // Descontar stock de los productos
            foreach ($this->items as $item) {
                if ($item->product && $item->product->track_stock) {
                    if ($item->product->stock < $item->quantity) {
                        throw new \Exception("Stock insuficiente para el producto {$item->product_name}");
                    }
                    $item->product->decrement('stock', $item->quantity);
                }
            }

            // Cambiar estado a confirmada
            $this->status = 'confirmed';
            $this->save();

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verificar si la venta puede ser anulada
     */
    public function canBeCancelled(): bool
    {
        return $this->status !== 'cancelled';
    }

    /**
     * Verificar si la venta puede ser confirmada
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Obtener el total de IVA
     */
    public function getTotalIvaAttribute(): float
    {
        return $this->iva_5 + $this->iva_10;
    }

    /**
     * Obtener etiqueta de estado
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Borrador',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Anulada',
            default => $this->status,
        };
    }
}
