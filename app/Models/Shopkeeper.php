<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shopkeeper extends Model
{
      protected $fillable = [
        'name',
        'email',
        'cnpj',
        'password',
        'balance',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // Um lojista tem uma carteira
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'owner_id')->where('owner_type', Shopkeeper::class);
    }

    // Um lojista recebe transferências (mas não envia)
    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payee_id', 'id')
            ->where('payee_type', Shopkeeper::class);
    }
}
