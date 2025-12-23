<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $fillable = [
        'owner_id',
        'owner_type',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // Polimórfico: pode pertencer a User ou Shopkeeper
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    // Adicionar saldo
    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    // Subtrair saldo
    public function subtractBalance(float $amount): void
    {
        $this->decrement('balance', $amount);
    }

    // Verificar saldo
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
