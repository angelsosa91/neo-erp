<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'account_number',
        'account_name',
        'bank_name',
        'bank_code',
        'account_type',
        'currency',
        'initial_balance',
        'current_balance',
        'account_holder',
        'swift_code',
        'notes',
        'status',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    /**
     * Actualizar el saldo de la cuenta
     */
    public function updateBalance(): void
    {
        $deposits = $this->transactions()
            ->whereIn('type', ['deposit', 'transfer_in', 'interest'])
            ->where('status', 'completed')
            ->sum('amount');

        $withdrawals = $this->transactions()
            ->whereIn('type', ['withdrawal', 'transfer_out', 'check', 'charge'])
            ->where('status', 'completed')
            ->sum('amount');

        $this->current_balance = $this->initial_balance + $deposits - $withdrawals;
        $this->save();
    }

    /**
     * Obtener saldo disponible (incluyendo cheques pendientes)
     */
    public function getAvailableBalanceAttribute(): float
    {
        $pendingChecks = $this->checks()
            ->where('type', 'issued')
            ->whereIn('status', ['pending', 'deposited'])
            ->sum('amount');

        return $this->current_balance - $pendingChecks;
    }

    /**
     * Obtener transacciones no conciliadas
     */
    public function getUnreconciledTransactions()
    {
        return $this->transactions()
            ->where('reconciled', false)
            ->where('status', 'completed')
            ->orderBy('transaction_date', 'desc')
            ->get();
    }
}
