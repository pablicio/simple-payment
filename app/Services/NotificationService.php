<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;

class NotificationService
{
    /**
     * Notificar recebedor de transferência
     */
    public function notifyTransferReceived(Transaction $transaction): Notification
    {
        $payee = $transaction->payee;
        $payer = $transaction->payer;

        // Criar registro de notificação
        $notification = Notification::create([
            'transaction_id' => $transaction->id,
            'user_id' => $payee->id,
            'type' => Notification::TYPE_TRANSFER_RECEIVED,
            'channel' => Notification::CHANNEL_API,
            'message' => $this->buildTransferMessage($payer, $payee, $transaction->value),
            'payload' => [
                'email' => $payee->email,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->value,
                'payer_name' => $payer->name,
            ],
            'status' => Notification::STATUS_PENDING,
        ]);

        // Despachar job para envio assíncrono
        SendNotificationJob::dispatch($notification);

        return $notification;
    }

    /**
     * Construir mensagem de transferência recebida
     */
    private function buildTransferMessage(User $payer, User $payee, float $amount): string
    {
        return sprintf(
            'Olá %s! Você recebeu uma transferência de R$ %.2f de %s.',
            $payee->name,
            $amount,
            $payer->name
        );
    }

    /**
     * Reenviar notificação que falhou
     */
    public function retryNotification(Notification $notification): void
    {
        if (!$notification->shouldRetry()) {
            throw new \Exception('Notification cannot be retried');
        }

        SendNotificationJob::dispatch($notification);
    }

    /**
     * Obter status das notificações de uma transação
     */
    public function getTransactionNotificationStatus(int $transactionId): array
    {
        $notifications = Notification::where('transaction_id', $transactionId)->get();

        return [
            'total' => $notifications->count(),
            'sent' => $notifications->where('status', Notification::STATUS_SENT)->count(),
            'pending' => $notifications->where('status', Notification::STATUS_PENDING)->count(),
            'failed' => $notifications->where('status', Notification::STATUS_FAILED)->count(),
            'notifications' => $notifications->toArray(),
        ];
    }
}
