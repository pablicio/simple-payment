<?php

namespace App\Services;

use App\Models\User;
use App\Models\Shopkeeper;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransferService
{
    const AUTHORIZER_URL = 'https://util.devi.tools/api/v2/authorize';
    const NOTIFIER_URL = 'https://util.devi.tools/api/v1/notify';

    /**
     * Executar transferência entre usuários
     */
    public function transfer(int $payerId, int $payeeId, float $amount, ?string $payeeType = null)
    {
        return DB::transaction(function () use ($payerId, $payeeId, $amount, $payeeType) {
            // 1. Buscar pagador (sempre User)
            $payer = User::find($payerId);
            if (!$payer) {
                throw new \Exception('Payer not found', 404);
            }

            // 2. Verificar se pagador é lojista (não pode enviar)
            if ($payer->is_shopkeeper ?? false) {
                throw new \Exception('Shopkeepers cannot send transfers', 403);
            }

            // 3. Buscar recebedor (User ou Shopkeeper)
            $payee = $this->findPayee($payeeId, $payeeType);
            if (!$payee) {
                throw new \Exception('Payee not found', 404);
            }

            // 4. Não pode transferir para si mesmo
            if ($payer->id === $payeeId && $payeeType === User::class) {
                throw new \Exception('Cannot transfer to yourself', 400);
            }

            // 5. Verificar saldo
            if (!$payer->hasSufficientBalance($amount)) {
                throw new \Exception('Insufficient balance', 400);
            }

            // 6. Consultar autorizador externo
            if (!$this->authorize()) {
                throw new \Exception('Transfer not authorized', 403);
            }

            // 7. Criar transação com status pending
            $transaction = Transaction::create([
                'payer_id' => $payer->id,
                'payee_id' => $payeeId,
                'payee_type' => $payeeType ?? User::class,
                'amount' => $amount,
                'status' => Transaction::STATUS_PENDING,
            ]);

            // 8. Descontar do pagador
            $payer->decrement('balance', $amount);

            // 9. Adicionar ao recebedor
            $payee->increment('balance', $amount);

            // 10. Marcar transação como completa
            $transaction->markAsCompleted();

            // 11. Notificar o recebedor (assíncrono, não bloqueia)
            $this->notifyPayee($payee, $payer, $amount);

            return $transaction->load('payer', 'payee');
        });
    }

    /**
     * Encontrar recebedor (User ou Shopkeeper)
     */
    private function findPayee(int $payeeId, ?string $payeeType)
    {
        if ($payeeType === 'shopkeeper' || $payeeType === Shopkeeper::class) {
            return Shopkeeper::find($payeeId);
        }

        // Padrão: buscar como User primeiro
        $user = User::find($payeeId);
        if ($user) {
            return $user;
        }

        // Se não encontrar User, tenta Shopkeeper
        return Shopkeeper::find($payeeId);
    }

    /**
     * Consultar serviço autorizador externo
     */
    private function authorize(): bool
    {
        try {
            $response = Http::timeout(5)->get(self::AUTHORIZER_URL);
            
            // Simular resposta do mock
            return $response->status() === 200;
        } catch (\Exception $e) {
            // Se falhar, considera não autorizado
            return false;
        }
    }

    /**
     * Notificar o recebedor
     */
    private function notifyPayee($payee, User $payer, float $amount): void
    {
        try {
            Http::timeout(5)->post(self::NOTIFIER_URL, [
                'payee_id' => $payee->id,
                'payee_name' => $payee->name,
                'payee_email' => $payee->email,
                'payer_name' => $payer->name,
                'amount' => $amount,
                'message' => "Você recebeu R$ {$amount} de {$payer->name}",
            ]);
        } catch (\Exception $e) {
            // Log do erro mas não falha a transferência
            \Log::warning('Notification failed', ['error' => $e->getMessage()]);
        }
    }
}
