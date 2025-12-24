<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    // Status possíveis
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    // Tipos de notificação
    const TYPE_TRANSFER_RECEIVED = 'transfer_received';
    const TYPE_TRANSFER_SENT = 'transfer_sent';

    // Canais
    const CHANNEL_API = 'api';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';

    protected $fillable = [
        'transaction_id',
        'user_id',
        'type',
        'channel',
        'message',
        'payload',
        'status',
        'response',
        'attempts',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Relacionamento com Transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Relacionamento com User (recebedor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar como enviada
     */
    public function markAsSent(string $response = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'response' => $response,
        ]);
    }

    /**
     * Registrar falha
     */
    public function markAsFailed(string $errorMessage, bool $finalFailure = false): void
    {
        $this->increment('attempts');
        
        $updates = [
            'response' => $errorMessage,
        ];

        if ($finalFailure) {
            $updates['status'] = self::STATUS_FAILED;
            $updates['failed_at'] = now();
        }

        $this->update($updates);
    }

    /**
     * Verificar se deve tentar novamente
     */
    public function shouldRetry(int $maxAttempts = 3): bool
    {
        return $this->status === self::STATUS_PENDING 
            && $this->attempts < $maxAttempts;
    }

    /**
     * Scopers
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
