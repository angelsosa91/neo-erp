<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterMovement extends Model
{
    protected $fillable = [
        'cash_register_id',
        'type',
        'concept',
        'description',
        'amount',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
}
