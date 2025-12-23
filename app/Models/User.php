<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const TYPE_COMMON = 'common';
    const TYPE_MERCHANT = 'merchant';

    protected $fillable = [
        'name',
        'email',
        'document', // CPF ou CNPJ
        'password',
        'type',
        'balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:2',
    ];

    // Um usuário pode fazer várias transferências (como pagador)
    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payer_id');
    }

    // Um usuário pode receber várias transferências (como recebedor)
    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payee_id');
    }

    // Verificar se é lojista/comerciante
    public function isMerchant(): bool
    {
        return $this->type === self::TYPE_MERCHANT;
    }

    // Verificar se pode enviar transferência
    public function canSendTransfer(): bool
    {
        return !$this->isMerchant();
    }

    // Verificar se tem saldo suficiente
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
