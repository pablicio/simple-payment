<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'password',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // Um usuário tem uma carteira
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'owner_id')->where('owner_type', User::class);
    }

    // Um usuário pode fazer várias transferências (como pagador)
    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payer_id');
    }

    // Um usuário pode receber várias transferências (como recebedor)
    public function receivedTransactions(): HasMany
    {
        return $this->hasManyThrough(
            Transaction::class,
            User::class,
            'id',
            'payee_id',
            'id',
            'id'
        )->where('payee_type', User::class);
    }

    // Verificar se tem saldo suficiente
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
