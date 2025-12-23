<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'payer_id',
        'payee_id',
        'amount',
        'status',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Quem está enviando
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    // Quem está recebendo
    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    // Marcar como completa
    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    // Marcar como falha
    public function markAsFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    // Verificar se está pendente
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
